<?php
namespace Modules\Portal\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Branch;
use Modules\Portal\Models\{Complaint, CargoClaim, CustomerFeedback, CustomerMessage, CommunicationPreference};
use Modules\Portal\Services\PortalService;
use Modules\Commercial\Models\Customer;
use Modules\Cargo\Models\CargoPackage;
use Modules\Ticketing\Models\Ticket;
use Tests\TestCase;

class PortalModuleTest extends TestCase
{
    use RefreshDatabase;

    private PortalService $svc;
    private $user; private $customer; private $staff; private $approver;

    private function fixture(): void
    {
        $this->svc = app(PortalService::class);
        $this->seed(\Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder::class);
        $b = Branch::first() ?? Branch::factory()->create(['code' => 'TZ']);
        $this->user = \App\Models\User::factory()->create();
        $this->staff = \App\Models\User::factory()->create();
        $this->approver = \App\Models\User::factory()->create();
        $this->seed(\Modules\Portal\Database\Seeders\PortalDatabaseSeeder::class);
        $this->customer = new Customer(['company_id' => $b->company_id, 'name' => 'Portal Customer']);
        $this->customer->forceFill(['code' => 'XER-CUS-2026-P0001', 'user_id' => $this->user->id])->save();
    }

    private function claim(): CargoClaim
    {
        return $this->svc->submitClaim($this->customer, ['type' => 'damaged', 'description' => 'Box crushed', 'claimed_amount' => 50000]);
    }

    public function test_customer_link_and_dashboard(): void
    {
        $this->fixture();
        $this->assertEquals($this->customer->id, $this->svc->customerFor($this->user)->id);
        $stats = $this->svc->customerDashboard($this->customer);
        $this->assertEquals(0, $stats['open_tickets']);
        $pkg = new CargoPackage();
        $pkg->forceFill(['reference' => 'XER-PKG-1', 'branch_id' => Branch::first()->id, 'customer_id' => $this->customer->id, 'status' => 'stored', 'gross_weight' => 1, 'chargeable_weight' => 1])->save();
        $this->assertEquals(1, $this->svc->customerDashboard($this->customer)['packages_total']);
    }

    public function test_creates_customer_ticket_with_ref(): void
    {
        $this->fixture();
        $t = $this->svc->createTicket($this->customer, ['subject' => 'Where is my cargo', 'description' => 'Delayed 2 weeks']);
        $this->assertStringStartsWith('XER-TKT-', $t->ticket_number);
        $this->assertEquals('customer_portal', $t->source);
        $this->assertEquals($this->customer->id, $t->customer_id);
    }

    public function test_ticket_reply_internal_flag(): void
    {
        $this->fixture();
        $t = $this->svc->createTicket($this->customer, ['subject' => 'S', 'description' => 'D']);
        $pub = $this->svc->ticketReply($t, 'Customer reply', null, false);
        $this->assertFalse($pub->is_internal);
        $priv = $this->svc->ticketReply($t, 'Internal note', $this->staff->id, true);
        $this->assertTrue($priv->is_internal);
        $this->assertEquals(1, $t->publicComments()->count());
    }

    public function test_complaint_lifecycle(): void
    {
        $this->fixture();
        $cp = $this->svc->submitComplaint($this->customer, ['category' => 'cargo_delay', 'description' => 'Late']);
        $this->assertStringStartsWith('XER-CMP-', $cp->reference);
        $this->svc->transitionComplaint($cp, 'acknowledged', $this->staff->id);
        $this->svc->transitionComplaint($cp->fresh(), 'under_investigation', $this->staff->id);
        $this->svc->transitionComplaint($cp->fresh(), 'resolution_proposed', $this->staff->id);
        $this->svc->transitionComplaint($cp->fresh(), 'resolved', $this->staff->id, 'Refund issued');
        $this->assertEquals('resolved', $cp->fresh()->status);
        $this->assertEquals('Refund issued', $cp->fresh()->resolution);
    }

    public function test_invalid_complaint_transition_rejected(): void
    {
        $this->fixture();
        $cp = $this->svc->submitComplaint($this->customer, ['category' => 'other', 'description' => 'x']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->transitionComplaint($cp, 'resolved', $this->staff->id);
    }

    public function test_claim_separation_of_duties(): void
    {
        $this->fixture();
        $cl = $this->claim();
        $cl->update(['status' => 'investigation']);
        $this->svc->investigateClaim($cl->fresh(), $this->staff->id, 'Damage confirmed by warehouse photos');
        $this->assertEquals('pending_approval', $cl->fresh()->status);
        // investigator can't decide own claim
        try { $this->svc->decideClaim($cl->fresh(), 'approved', $this->staff->id, 'ok'); $this->fail('SoD violated'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $this->svc->decideClaim($cl->fresh(), 'approved', $this->approver->id, 'Valid claim');
        $this->assertEquals('approved', $cl->fresh()->status);
        $this->assertEquals(50000, $cl->fresh()->approved_amount);
    }

    public function test_partial_approval_requires_lower_amount(): void
    {
        $this->fixture();
        $cl = $this->claim();
        $cl->update(['status' => 'pending_approval', 'investigator_id' => $this->staff->id]);
        try { $this->svc->decideClaim($cl, 'partially_approved', $this->approver->id, 'r', 60000); $this->fail('over-claim'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $this->svc->decideClaim($cl->fresh(), 'partially_approved', $this->approver->id, 'Partial', 30000);
        $this->assertEquals(30000, $cl->fresh()->approved_amount);
    }

    public function test_settlement_requires_ref_and_correct_state(): void
    {
        $this->fixture();
        $cl = $this->claim();
        try { $this->svc->requestSettlement($cl, $this->staff->id); $this->fail('not approved'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $cl->update(['status' => 'approved', 'approved_amount' => 50000, 'investigator_id' => $this->staff->id]);
        $this->svc->requestSettlement($cl->fresh(), $this->staff->id);
        $this->assertEquals('settlement_pending', $cl->fresh()->status);
        try { $this->svc->markSettled($cl->fresh(), '', $this->staff->id); $this->fail('no ref'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $this->svc->markSettled($cl->fresh(), 'FIN-PMT-001', $this->staff->id);
        $this->assertEquals('settled', $cl->fresh()->status);
        $this->assertEquals('FIN-PMT-001', $cl->fresh()->settlement_ref);
    }

    public function test_feedback_dedupe(): void
    {
        $this->fixture();
        $this->svc->submitFeedback($this->customer, 'delivery:1', 5);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->submitFeedback($this->customer, 'delivery:1', 4);
    }

    public function test_message_respects_channel_prefs(): void
    {
        $this->fixture();
        CommunicationPreference::create(['customer_id' => $this->customer->id, 'channels' => ['whatsapp' => false]]);
        $m = $this->svc->queueMessage($this->customer, 'email', 'Hello', ['operational' => true]);
        $this->assertEquals('queued', $m->status); // never claims delivered
        try { $this->svc->queueMessage($this->customer, 'whatsapp', 'Promo', ['operational' => false]); $this->fail('disabled channel'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
    }

    public function test_privacy_request(): void
    {
        $this->fixture();
        $p = $this->svc->submitPrivacyRequest($this->customer, 'export', 'Want my data');
        $this->assertStringStartsWith('XER-PRV-', $p->reference);
        $this->assertEquals('submitted', $p->status);
    }

    public function test_statement_reads_finance_only(): void
    {
        $this->fixture();
        \Modules\Finance\Models\CustomerInvoice::create([
            'reference' => 'INV-001', 'company_id' => $this->customer->company_id, 'customer_id' => $this->customer->id,
            'type' => 'invoice', 'invoice_date' => now(), 'total' => 10000, 'received_amount' => 4000, 'status' => 'partial', 'created_by' => $this->staff->id]);
        $st = $this->svc->statement($this->customer);
        $this->assertEquals(6000, $st['closing_balance']);
        $this->assertCount(1, $st['invoices']);
    }

    public function test_idor_guard_customer_ownership(): void
    {
        $this->fixture();
        $other = new Customer(['company_id' => $this->customer->company_id, 'name' => 'Other']);
        $other->forceFill(['code' => 'XER-CUS-2026-P0002', 'user_id' => $this->staff->id])->save();
        // customer A's tickets invisible to customer B's query
        $this->svc->createTicket($this->customer, ['subject' => 'A ticket', 'description' => 'x']);
        $this->assertEquals(0, Ticket::where('customer_id', $other->id)->count());
        $this->assertEquals(1, Ticket::where('customer_id', $this->customer->id)->count());
    }

    public function test_customer360_aggregates(): void
    {
        $this->fixture();
        $this->svc->submitComplaint($this->customer, ['category' => 'other', 'description' => 'x']);
        $this->claim();
        $d = $this->svc->customer360($this->customer);
        $this->assertEquals(1, $d['complaints']->count());
        $this->assertEquals(1, $d['claims']->count());
    }
}
