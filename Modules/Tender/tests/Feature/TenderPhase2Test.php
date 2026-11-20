<?php

namespace Modules\Tender\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Company;
use Modules\Tender\Database\Seeders\TenderDatabaseSeeder;
use Modules\Tender\Models\{Tender, TenderContract, TenderSecurity};
use Modules\Tender\Services\{SecurityValidityService, TenderIntelligence, TenderWorkflow};
use Tests\TestCase;

class TenderPhase2Test extends TestCase
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
            'company_id' => Company::value('id'), 'reference' => 'T-P2', 'buyer_name' => 'TANESCO',
            'buyer_type' => 'public', 'category' => 'works', 'title' => 'Line works',
            'stage' => 'evaluation', 'currency' => 'TZS',
            'submission_deadline' => now()->subDay(), 'validity_days' => 90,
        ]);
    }

    public function test_clarification_moves_evaluation_to_clarification_stage(): void
    {
        $res = $this->actingAs($this->admin)->postJson("/tender/{$this->t->id}/clarifications", [
            'direction' => 'received', 'subject' => 'BOQ item 4 qty', 'content' => 'Please clarify.']);
        $res->assertCreated();
        $this->assertSame('clarification', $this->t->fresh()->stage);
        $this->assertDatabaseHas('tender_clarifications', ['tender_id' => $this->t->id, 'status' => 'open']);
    }

    public function test_clarification_response_marks_responded(): void
    {
        $c = $this->t->clarifications()->create(['direction' => 'received',
            'subject' => 'x', 'content' => 'q', 'status' => 'open']);
        $this->actingAs($this->admin)->postJson("/tender/clarifications/{$c->id}/respond",
            ['response' => 'Corrected BOQ attached.'])->assertOk();
        $this->assertSame('responded', $c->fresh()->status);
    }

    public function test_task_raci_assignment_and_update(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        $res = $this->actingAs($this->admin)->postJson("/tender/{$this->t->id}/tasks", [
            'title' => 'Price section 3', 'raci' => 'R', 'assignee_id' => $member->id,
            'due_at' => now()->addDays(3)]);
        $res->assertCreated();
        $taskId = $res->json('id');
        $this->actingAs($this->admin)->patchJson("/tender/tasks/{$taskId}", ['status' => 'done'])->assertOk();
        $this->assertDatabaseHas('tender_tasks', ['id' => $taskId, 'status' => 'done']);
    }

    public function test_award_record_transitions_to_awarded(): void
    {
        $this->t->update(['stage' => 'evaluation']);
        $res = $this->actingAs($this->admin)->postJson("/tender/{$this->t->id}/awards", [
            'award_ref' => 'AW-9', 'awarded_at' => now()->toDateString(),
            'value' => '150000000', 'currency' => 'TZS']);
        $res->assertCreated();
        $this->assertSame('awarded', $this->t->fresh()->stage);
        $this->assertDatabaseHas('tender_awards', ['tender_id' => $this->t->id, 'award_ref' => 'AW-9']);
    }

    public function test_variation_approval_adjusts_contract(): void
    {
        $contract = TenderContract::create(['tender_id' => $this->t->id, 'contract_ref' => 'C-1',
            'value' => '1000.00', 'currency' => 'TZS', 'status' => 'active',
            'ends_at' => now()->addDays(100)]);
        $res = $this->actingAs($this->admin)->postJson("/tender/contracts/{$contract->id}/variations", [
            'type' => 'value', 'description' => 'Extra works', 'value_delta' => '250.00',
            'days_delta' => 30]);
        $res->assertCreated();
        $vId = $res->json('id');
        $this->actingAs($this->admin)->postJson("/tender/variations/{$vId}/approve")->assertOk();
        $c = $contract->fresh();
        $this->assertSame(0, bccomp((string) $c->value, '1250.00', 2));
        $this->assertSame(30, (int) $contract->ends_at->diffInDays($c->ends_at));
    }

    public function test_security_validity_ppra_28_day_rule(): void
    {
        // submission 90 days validity + 28 → required ~118 days after deadline
        $sec = TenderSecurity::create(['tender_id' => $this->t->id, 'type' => 'tender_security',
            'form' => 'bank_guarantee', 'amount' => '5000000', 'expires_at' => now()->addDays(60),
            'required_until' => $this->t->submission_deadline->copy()->addDays(118), 'status' => 'issued']);
        $check = app(SecurityValidityService::class)->check($sec);
        $this->assertFalse($check['ok']);         // expires before required
        $this->assertGreaterThan(0, $check['shortfall_days']);
        $sec->update(['expires_at' => now()->addDays(200)]);
        $this->assertTrue(app(SecurityValidityService::class)->check($sec->fresh())['ok']);
    }

    public function test_intelligence_report_structure(): void
    {
        $data = app(TenderIntelligence::class)->analyze($this->t);
        foreach (['assessment_score','decision','requirements','expired_qualifications',
                  'capital_required','submission_readiness'] as $k) {
            $this->assertArrayHasKey($k, $data);
        }
    }

    public function test_unauthorized_cannot_record_award(): void
    {
        $plain = User::factory()->create(['status' => 'active']);
        $this->actingAs($plain)->postJson("/tender/{$this->t->id}/awards", [
            'awarded_at' => now()->toDateString(), 'value' => '1', 'currency' => 'TZS'])
            ->assertForbidden();
    }
}
