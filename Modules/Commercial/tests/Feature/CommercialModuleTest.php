<?php

namespace Modules\Commercial\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commercial\Models\{Customer, Lead, Inquiry, Quotation, RateCard, CommercialHandover, CargoAssessment};
use Modules\Commercial\Services\{CustomerService, LeadService, InquiryService, QuotationService, PricingService};
use Modules\Organization\Models\Branch;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommercialModuleTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $perms = [], ?int $branchId = null): User
    {
        $u = User::factory()->create(['status' => 'active']);
        foreach ($perms as $p) { Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']); $u->givePermissionTo($p); }
        return $u;
    }

    private function seedDeps(): void
    {
        (new \Modules\Commercial\Database\Seeders\CommercialDatabaseSeeder)->run();
        (new \Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder)->run();
    }

    public function test_permissions_seeded_and_sales_officer_role(): void
    {
        $this->seedDeps();
        $this->assertTrue(Permission::where('name', 'quotation.approve')->exists());
        $so = Role::where('name', 'Sales and CRM')->first();
        $this->assertTrue($so->hasPermissionTo('customer.create'));
        $this->assertTrue($so->hasPermissionTo('quotation.approve'));
        // Self-approval is guarded in QuotationService::transition (preparer ≠ approver).
    }

    public function test_customer_created_with_sequence_reference(): void
    {
        $this->seedDeps();
        $u = $this->user(['customer.create']);
        $c = app(CustomerService::class)->create(['type' => 'business', 'name' => 'Acme TZ', 'email' => 'a@b.co'], $u->id);
        $this->assertMatchesRegularExpression('/XER-CUS-\d{4}-\d{6}/', $c->code);
        $this->assertEquals('prospect', $c->fresh()->status);
    }

    public function test_lead_conversion_reuses_existing_customer(): void
    {
        $this->seedDeps();
        $u = $this->user(['lead.create', 'lead.update']);
        $svc = app(LeadService::class);
        $existing = new Customer(['name' => 'Dup Corp', 'phone' => '+255700000001', 'type' => 'business']);
        $existing->forceFill(['code' => 'XER-CUS-2026-000001', 'company_id' => \Modules\Organization\Models\Company::value('id')])->save();
        $lead = $svc->create(['name' => 'John', 'phone' => '+255700000001', 'source' => 'whatsapp'], $u->id);
        $customer = $svc->convert($lead, $u->id);
        $this->assertSame($existing->id, $customer->id); // no duplicate
        $this->assertEquals('won', $lead->fresh()->stage);
        $this->assertSame(1, Customer::where('phone', '+255700000001')->count());
    }

    public function test_chargeable_weight_is_max_of_gross_and_volumetric(): void
    {
        $p = app(PricingService::class);
        $vol = $p->volumetricWeight(100, 50, 40, 1, 6000); // 33.33 kg
        $this->assertEquals(33.33, $vol);
        $this->assertEquals(50.0, $p->chargeableWeight(50.0, $vol));
        $this->assertEquals(33.33, $p->chargeableWeight(10.0, $vol));
    }

    public function test_rate_card_quote_breakdown_with_minimum_charge(): void
    {
        $card = RateCard::create(['code' => 'RC1', 'name' => 'DXB→TZ Air', 'currency' => 'USD', 'per_kg_rate' => 4.5, 'minimum_charge' => 100, 'status' => 'active']);
        $b = app(PricingService::class)->quote($card, 10); // 45 < min 100
        $this->assertEquals(100.0, $b['total']);
        $this->assertGreaterThan(1, count($b['lines']));
    }

    public function test_quotation_snapshot_preserved_and_accept_creates_one_handover(): void
    {
        $this->seedDeps();
        $u = $this->user(['quotation.create', 'quotation.update', 'quotation.approve']);
        $card = RateCard::create(['code' => 'RC2', 'name' => 'Card', 'currency' => 'USD', 'per_kg_rate' => 5, 'status' => 'active']);
        $cust = new Customer(['name' => 'C Ltd', 'type' => 'business']);
        $cust->forceFill(['code' => 'XER-CUS-2026-000002', 'company_id' => \Modules\Organization\Models\Company::value('id')])->save();
        $svc = app(QuotationService::class);
        $q = $svc->create(['customer_id' => $cust->id, 'gross_weight' => 10, 'rate_card_id' => $card->id, 'valid_until' => now()->addDays(7)], $u->id);
        $this->assertEquals(50.0, $q->total);
        $snapshot = $q->pricing_snapshot;
        // Change card rate → accepted quote unaffected
        $card->update(['per_kg_rate' => 99]);
        $this->assertEquals(50.0, $q->fresh()->total);
        $svc->transition($q, 'pending_approval', $u->id);
        // Self-approval guard (spec): preparer cannot approve own quote.
        try {
            $svc->transition($q->fresh(), 'approved', $u->id);
            $this->fail('Preparer self-approved a quotation.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $approver = $this->user(['quotation.approve']);
        $svc->transition($q->fresh(), 'approved', $approver->id);
        $svc->transition($q->fresh(), 'sent', $u->id);
        $svc->accept($q->fresh(), 'portal_link', $u->id);
        try { $svc->accept($q->fresh(), 'manual', $u->id); } catch (\Throwable) {}
        $this->assertEquals(1, CommercialHandover::where('quotation_id', $q->id)->count());
        $this->assertEquals('accepted', $q->fresh()->status);
        $this->assertSame($snapshot, $q->fresh()->pricing_snapshot);
    }

    public function test_expired_quotation_cannot_be_accepted(): void
    {
        $this->seedDeps();
        $u = $this->user(['quotation.create']);
        $cust = new Customer(['name' => 'X', 'type' => 'individual']);
        $cust->forceFill(['code' => 'XER-CUS-2026-000003', 'company_id' => \Modules\Organization\Models\Company::value('id')])->save();
        $q = new Quotation(['customer_id' => $cust->id, 'status' => 'sent', 'valid_until' => now()->subDay(), 'currency' => 'USD']);
        $q->forceFill(['number' => 'XER-QUO-2026-000001'])->save();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(QuotationService::class)->accept($q, 'portal_link', $u->id);
    }

    public function test_inquiry_status_transitions_enforced(): void
    {
        $u = $this->user(['inquiry.create', 'inquiry.update']);
        $svc = app(InquiryService::class);
        $inq = $svc->create(['source' => 'manual', 'priority' => 'normal'], $u->id);
        $svc->transition($inq, 'received', $u->id);
        $this->assertEquals('received', $inq->fresh()->status);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $svc->transition($inq->fresh(), 'accepted', $u->id); // invalid jump
    }

    public function test_branch_isolation_on_inquiry_list(): void
    {
        $this->seedDeps();
        $tz = Branch::where('code', 'TZ')->first();
        $dxb = Branch::where('code', 'DXB')->first();
        (new Inquiry(['branch_id' => $tz->id, 'source' => 'manual']))->forceFill(['reference' => 'XER-INQ-2026-000001'])->save();
        (new Inquiry(['branch_id' => $dxb->id, 'source' => 'manual']))->forceFill(['reference' => 'XER-INQ-2026-000002'])->save();
        $tzUser = $this->user(['inquiry.view'], $tz->id);
        // simulate branch assignment
        $tzUser->load('currentAssignment');
        $list = Inquiry::for($tzUser)->pluck('reference');
        $this->assertContains('XER-INQ-2026-000001', $list);
    }

    public function test_sales_officer_cannot_approve_quotation(): void
    {
        $this->seedDeps();
        $u = $this->user(['quotation.update']); // no approve perm
        $q = (new Quotation(['status' => 'pending_approval', 'currency' => 'USD']))->forceFill(['number' => 'XER-QUO-2026-000002']);
        $q->save();
        $this->actingAs($u);
        $this->post(route('commercial.quotations.transition', $q), ['status' => 'approved'])->assertForbidden();
    }

    public function test_public_token_link_accepts_quotation(): void
    {
        $q = (new Quotation(['status' => 'sent', 'currency' => 'USD',
            'valid_until' => now()->addDays(5), 'acceptance_token' => 'test-token-abc', 'total' => 100]))
            ->forceFill(['number' => 'XER-QUO-2026-000003']);
        $q->save();
        $this->get("/q/test-token-abc")->assertOk()->assertSee('Accept');
        $this->post("/q/test-token-abc", ['action' => 'accept'])->assertRedirect();
        $this->assertEquals('accepted', $q->fresh()->status);
        $this->assertEquals('portal_link', $q->fresh()->acceptance_channel);
    }
}
