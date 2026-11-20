<?php

namespace Modules\Tender\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Tender\Database\Seeders\TenderDatabaseSeeder;
use Modules\Tender\Models\Tender;
use Modules\Tender\Models\TenderDocument;
use Modules\Tender\Services\{BidNoBidService, BoqService, SubmissionGate, TenderWorkflow};
use Tests\TestCase;

class TenderModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tender $t;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(TenderDatabaseSeeder::class);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        $this->t = Tender::create([
            'company_id' => Company::value('id'), 'reference' => 'T-001', 'buyer_name' => 'TRA',
            'buyer_type' => 'public', 'category' => 'services', 'title' => 'Cleaning services',
            'stage' => 'initial_review', 'currency' => 'TZS',
            'submission_deadline' => now()->addDays(30),
        ]);
    }

    public function test_duplicate_reference_rejected(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Tender::create(['company_id' => $this->t->company_id, 'reference' => 'T-001',
            'buyer_name' => 'TRA', 'buyer_type' => 'public', 'category' => 'goods', 'title' => 'x']);
    }

    public function test_bid_assessment_scores_and_recommends(): void
    {
        $a = app(BidNoBidService::class)->assess($this->t, [
            'commercial' => ['score' => 80], 'technical' => ['score' => 70],
            'compliance' => ['score' => 90], 'strategic' => ['score' => 60], 'risk' => ['score' => 50],
        ], $this->admin);
        $this->assertSame(74, $a->total_score); // 24+17.5+18+9+5
    }

    public function test_no_bid_requires_reason_and_closes(): void
    {
        $svc = app(BidNoBidService::class);
        $svc->assess($this->t, ['commercial'=>['score'=>10],'technical'=>['score'=>10],
            'compliance'=>['score'=>10],'strategic'=>['score'=>10],'risk'=>['score'=>10]], $this->admin);
        $this->expectException(ValidationException::class);
        $svc->decide($this->t, 'no_bid', $this->admin);
    }

    public function test_bid_decision_advances_stage(): void
    {
        $svc = app(BidNoBidService::class);
        $svc->assess($this->t, ['commercial'=>['score'=>80],'technical'=>['score'=>80],
            'compliance'=>['score'=>80],'strategic'=>['score'=>80],'risk'=>['score'=>80]], $this->admin);
        $svc->decide($this->t, 'bid', $this->admin);
        $this->assertSame('preparation', $this->t->fresh()->stage);
    }

    public function test_cannot_skip_stages_via_api(): void
    {
        $this->expectException(ValidationException::class);
        app(TenderWorkflow::class)->transition($this->t, 'awarded', $this->admin);
    }

    public function test_boq_math_is_decimal_safe(): void
    {
        $boq = $this->t->boqs()->create(['version' => 1, 'overhead_pct' => 10, 'profit_pct' => 15, 'tax_pct' => 18]);
        $svc = app(BoqService::class);
        $svc->addItem($boq, ['item_no' => '1.1', 'description' => 'Cement', 'quantity' => '100',
            'material_cost' => '50.25', 'labour_cost' => '10.75']);
        $svc->addItem($boq, ['item_no' => '1.2', 'description' => 'Labour lump', 'quantity' => '1',
            'unit_rate' => '1000']);
        $tot = $svc->totals($boq);
        $this->assertSame('7100.0000', $tot['direct']);          // 100*61 + 1000
        $this->assertSame('710.0000', $tot['overhead']);
        $this->assertSame('7810.0000', $tot['cost']);
        $this->assertSame('1171.5000', $tot['profit']);
        $this->assertSame('8981.5000', $tot['subtotal']);
        $this->assertSame('1616.6700', $tot['tax']);
        $this->assertSame('10598.1700', $tot['grand_total']);
    }

    public function test_boq_preserves_original_quantity(): void
    {
        $boq = $this->t->boqs()->create(['version' => 1]);
        $i = app(BoqService::class)->addItem($boq, ['item_no' => '1', 'description' => 'Mod desc',
            'original_description' => 'Issuer desc', 'quantity' => '5']);
        $this->assertSame('Issuer desc', $i->original_description);
        $this->assertSame(0, bccomp((string) $i->original_quantity, '5', 4));
    }

    public function test_submission_blocked_when_checklist_incomplete(): void
    {
        $this->t->update(['stage' => 'submission_ready']);
        $this->assertFalse(app(SubmissionGate::class)->ready($this->t));
        $doc = TenderDocument::create(['tender_id' => $this->t->id, 'category' => 'submission',
            'title' => 'receipt', 'path' => 'x', 'original_name' => 'r.pdf', 'mime_type' => 'application/pdf',
            'size' => 1, 'uploaded_by' => $this->admin->id]);
        $r = $this->actingAs($this->admin)->post(route('tender.submit', $this->t),
            ['method' => 'nest', 'evidence_document_id' => $doc->id]);
        $r->assertStatus(422);
    }

    public function test_full_pipeline_to_award(): void
    {
        $t = $this->t;
        $svc = app(BidNoBidService::class);
        $svc->assess($t, ['commercial'=>['score'=>80],'technical'=>['score'=>80],
            'compliance'=>['score'=>80],'strategic'=>['score'=>80],'risk'=>['score'=>80]], $this->admin);
        $svc->decide($t, 'bid', $this->admin);
        $t = $t->fresh();
        $wf = app(TenderWorkflow::class);
        foreach (['boq','costing','technical_proposal','financial_proposal','internal_review','approval','submission_ready'] as $s) {
            if ($s === 'submission_ready') {
                $t->approvals()->create(['step' => 'management', 'approver_id' => $this->admin->id,
                    'decision' => 'approved', 'decided_at' => now()]);
                $boq = $t->boqs()->create(['version' => 1]);
                app(BoqService::class)->addItem($boq, ['item_no' => '1', 'description' => 'x',
                    'quantity' => '1', 'unit_rate' => '1000']);
            }
            $t = $wf->transition($t, $s, $this->admin);
        }
        $doc = TenderDocument::create(['tender_id' => $t->id, 'category' => 'submission',
            'title' => 'ack', 'path' => 'x', 'original_name' => 'a.pdf', 'mime_type' => 'application/pdf',
            'size' => 1, 'uploaded_by' => $this->admin->id]);
        $this->actingAs($this->admin)->post(route('tender.submit', $t),
            ['method' => 'nest', 'platform_ref' => 'NEST-1', 'evidence_document_id' => $doc->id]);
        $this->assertSame('submitted', $t->fresh()->stage);
        $t = $wf->transition($t->fresh(), 'evaluation', $this->admin);
        $this->actingAs($this->admin)->post(route('tender.award', $t),
            ['contract_ref' => 'C-900', 'value' => '5000000']);
        $this->assertSame('awarded', $t->fresh()->stage);
        $this->assertDatabaseHas('tender_contracts', ['contract_ref' => 'C-900', 'value' => '5000000.00']);
    }

    public function test_security_validity_checked_in_gate(): void
    {
        $t = Tender::create(['company_id' => $this->t->company_id, 'reference' => 'T-SEC',
            'buyer_name' => 'MoF', 'buyer_type' => 'public', 'category' => 'works', 'title' => 'x',
            'stage' => 'submission_ready', 'security_required' => '1000',
            'submission_deadline' => now()->addDays(10)]);
        $t->assessment()->create(['scores' => [], 'total_score' => 80, 'decision' => 'bid', 'assessed_by' => $this->admin->id]);
        $t->securities()->create(['type' => 'tender_security', 'amount' => 1000,
            'expires_at' => now()->addDays(5), 'required_until' => now()->addDays(40),
            'status' => 'issued']); // expires BEFORE required → invalid
        $gate = app(SubmissionGate::class)->checklist($t);
        $sec = collect($gate)->firstWhere('item', 'Tender security valid');
        $this->assertFalse($sec['ok']);
    }

    public function test_immutable_submission_stage_guarded(): void
    {
        $this->t->update(['stage' => 'submission_ready']);
        $this->expectException(ValidationException::class);
        app(TenderWorkflow::class)->transition($this->t, 'submitted', $this->admin);
    }
}
