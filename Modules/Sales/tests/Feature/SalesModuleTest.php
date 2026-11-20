<?php
namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commercial\Models\Customer;
use Modules\Commercial\Models\RateCard;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Sales\Models\CommercialCampaign;
use Modules\Sales\Models\CommercialPartner;
use Modules\Sales\Models\CommissionRecord;
use Modules\Sales\Models\CommissionRule;
use Modules\Sales\Models\PipelineStage;
use Modules\Sales\Services\SalesService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesModuleTest extends TestCase
{
    use RefreshDatabase;

    private SalesService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder::class);
        $this->seed(\Modules\Sales\Database\Seeders\SalesDatabaseSeeder::class);
        $this->svc = app(SalesService::class);
    }

    private function customer(): Customer
    {
        static $i = 0; $i++;
        $b = \Modules\Organization\Models\Branch::first();
        return Customer::forceCreate(['company_id' => $b->company_id, 'branch_id' => $b->id,
            'name' => "Cust$i", 'code' => "C$i", 'email' => "c$i@t.com", 'status' => 'active']);
    }

    public function test_opportunity_lifecycle_and_weighted_pipeline(): void
    {
        $o = $this->svc->createOpportunity(['name' => 'Opp A', 'customer_id' => $this->customer()->id, 'expected_revenue' => 10000]);
        $this->assertEquals('open', $o->status);
        $nego = PipelineStage::where('code', 'negotiation')->first();
        $o = $this->svc->moveOpportunity($o, $nego->id);
        $this->assertEquals(65, $o->probability);
        $this->assertEquals(6500.0, $this->svc->weightedPipeline());
        $this->svc->closeOpportunity($o, 'won');
        $this->assertEquals(0.0, $this->svc->weightedPipeline()); // closed excluded
    }

    public function test_lost_opportunity_requires_reason(): void
    {
        $o = $this->svc->createOpportunity(['name' => 'X']);
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->closeOpportunity($o, 'lost');
    }

    public function test_revenue_target_uses_collected_not_quoted(): void
    {
        $c = $this->customer();
        CustomerInvoice::forceCreate(['reference' => 'INV1', 'company_id' => $c->company_id, 'customer_id' => $c->id, 'type' => 'sales',
            'invoice_date' => now(), 'currency' => 'TZS', 'subtotal' => 1000, 'total' => 1000,
            'received_amount' => 400, 'status' => 'partial', 'created_by' => User::factory()->create()->id]);
        $t = $this->svc->setTarget(['scope' => 'company', 'period_type' => 'monthly', 'period' => now()->format('Y-m'), 'metric' => 'revenue', 'target' => 500]);
        $a = $this->svc->targetAchievement($t);
        $this->assertEquals(400.0, $a['actual']); $this->assertEquals(80.0, $a['pct']);
    }

    public function test_contract_requires_signature_to_activate(): void
    {
        $c = $this->svc->createContract(['type' => 'corporate', 'customer_id' => $this->customer()->id], User::factory()->create()->id);
        foreach (['under_review','pending_approval','approved','pending_signature'] as $s) $c->transitionTo($s);
        $this->expectException(\InvalidArgumentException::class);
        $c->transitionTo('active'); // no signature evidence
    }

    public function test_contract_activation_with_signature_and_versioning(): void
    {
        $u = User::factory()->create();
        $c = $this->svc->createContract(['type' => 'corporate', 'terms' => ['rate' => 100]], $u->id);
        $v2 = $this->svc->amendContract($c, ['rate' => 90], $u->id);
        $this->assertEquals(2, $v2->version);
        $this->assertEquals(2, $c->versions()->count());
        $this->assertEquals(1, $c->versions()->where('status','current')->count());
    }

    public function test_partner_lifecycle(): void
    {
        $p = $this->svc->registerPartner(['type' => 'agent', 'name' => 'Agent A']);
        $this->assertStringStartsWith('XER-AGT-', $p->reference);
        $this->assertFalse($p->isCommissionable());
        foreach (['pending_verification','under_review','approved','active'] as $s) $p->transitionTo($s);
        $this->assertTrue($p->refresh()->isCommissionable());
        $this->assertFalse($p->canTransitionTo('approved')); // can't go back
    }

    public function test_broker_reference_prefix(): void
    {
        $b = $this->svc->registerPartner(['type' => 'broker', 'name' => 'B']);
        $this->assertStringStartsWith('XER-BKR-', $b->reference);
    }

    public function test_commission_skips_uncollected_revenue(): void
    {
        $r = CommissionRule::create(['beneficiary_type' => 'agent', 'beneficiary_id' => 1,
            'basis' => 'revenue_pct', 'rate' => 0.05, 'requires_collected' => true]);
        $this->assertNull($this->svc->generateCommission($r, 'invoice', 1, 10000, 0)); // nothing collected
        $rec = $this->svc->generateCommission($r, 'invoice', 1, 10000, 8000);
        $this->assertEquals(400.0, $rec->commission_amount); // 5% of 8000 collected, not 10000
    }

    public function test_commission_duplicate_prevention(): void
    {
        $r = CommissionRule::create(['beneficiary_type' => 'agent', 'beneficiary_id' => 2,
            'basis' => 'per_shipment', 'fixed_amount' => 500, 'requires_collected' => false]);
        $a = $this->svc->generateCommission($r, 'shipment', 7, 0);
        $b = $this->svc->generateCommission($r, 'shipment', 7, 0);
        $this->assertEquals($a->id, $b->id); // same record returned, no duplicate
        $this->assertEquals(1, CommissionRecord::where('source_type','shipment')->where('source_id',7)->count());
    }

    public function test_settlement_requires_finance_ref(): void
    {
        $r = CommissionRecord::create(['reference' => 'XER-CMS-1', 'beneficiary_type' => 'agent', 'beneficiary_id' => 1,
            'source_type' => 'invoice', 'source_id' => 1, 'basis_amount' => 100, 'commission_amount' => 5, 'status' => 'calculated']);
        $r->transitionTo('under_review'); $r->transitionTo('approved', User::factory()->create()->id); $r->transitionTo('payable');
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->settleCommission($r, ''); // no Finance ref
    }

    public function test_pricing_simulation_with_minimum_and_campaign(): void
    {
        RateCard::forceCreate(['code' => 'RC1', 'name' => 'DXB-DAR', 'origin_city' => 'Dubai',
            'destination_city' => 'Dar', 'service_type' => 'standard', 'cargo_category' => 'general',
            'customer_category' => 'retail', 'currency' => 'USD', 'base_rate' => 10, 'per_kg_rate' => 5, 'minimum_charge' => 100]);
        $camp = CommercialCampaign::create(['reference' => 'XER-CAM-1', 'name' => 'Promo', 'type' => 'discount',
            'start_date' => now()->subDay(), 'end_date' => now()->addDay(), 'discount_type' => 'percent',
            'discount_value' => 10, 'status' => 'active']);
        $res = $this->svc->simulatePrice(['origin' => 'Dubai', 'destination' => 'Dar', 'service_type' => 'standard',
            'customer_category' => 'retail', 'gross_weight' => 10, 'campaign_id' => $camp->id]);
        $this->assertTrue($res['minimum_applied']); // 10+50=60 < min 100
        $this->assertEquals(100.0, $res['price']);
        $this->assertEquals(10.0, $res['discount']); // 10% of 100
        $this->assertEquals(90.0, $res['final']);
    }

    public function test_expired_campaign_not_usable(): void
    {
        $c = CommercialCampaign::create(['reference' => 'XER-CAM-9', 'name' => 'Old', 'type' => 'discount',
            'start_date' => now()->subDays(30), 'end_date' => now()->subDays(1), 'discount_type' => 'percent',
            'discount_value' => 50, 'status' => 'active']);
        $this->assertFalse($c->isUsable());
        $this->assertEquals(0.0, $c->discountFor(1000)); // no discount outside validity
    }

    public function test_pricing_approval_separation_of_duties(): void
    {
        $u1 = User::factory()->create(); $u2 = User::factory()->create();
        $p = $this->svc->requestPricingApproval(['entity_type' => 'discount', 'requested_value' => 50, 'reason' => 'r'], $u1->id);
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->decidePricing($p, 'approved', $u1->id); // requester can't approve own
    }

    public function test_pricing_approval_by_other_user(): void
    {
        $u1 = User::factory()->create(); $u2 = User::factory()->create();
        $p = $this->svc->requestPricingApproval(['entity_type' => 'discount', 'requested_value' => 50, 'reason' => 'r'], $u1->id);
        $this->svc->decidePricing($p, 'approved', $u2->id);
        $this->assertEquals('approved', $p->status);
        $this->assertEquals($u2->id, $p->decided_by);
    }

    public function test_acquisition_attribution_and_milestones(): void
    {
        $c = $this->customer();
        $a = $this->svc->attributeCustomer($c, ['channel' => 'agent', 'partner_id' => 1]);
        $this->svc->recordMilestone($c, 'first_revenue_at');
        $this->assertNotNull($a->refresh()->first_revenue_at);
    }

    public function test_analytics_distinguishes_pipeline_vs_collected(): void
    {
        $c = $this->customer();
        $this->svc->createOpportunity(['name' => 'O', 'expected_revenue' => 5000]);
        CustomerInvoice::forceCreate(['reference' => 'INV9', 'company_id' => $c->company_id, 'customer_id' => $c->id, 'type' => 'sales',
            'invoice_date' => now(), 'currency' => 'TZS', 'subtotal' => 2000, 'total' => 2000,
            'received_amount' => 1500, 'status' => 'partial', 'created_by' => User::factory()->create()->id]);
        $a = $this->svc->revenueAnalytics();
        $this->assertEquals(5000.0, $a['pipeline_value']);
        $this->assertEquals(2000.0, $a['invoiced']);
        $this->assertEquals(1500.0, $a['collected']);
        $this->assertEquals(500.0, $a['outstanding']);
        $this->assertNotEquals($a['pipeline_value'], $a['invoiced']); // not interchangeable
    }
}
