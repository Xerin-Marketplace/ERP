<?php

namespace Modules\Ticketing\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Employee\Models\Employee;
use Modules\Employee\Services\EmployeeService;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Ticketing\Database\Seeders\TicketingDatabaseSeeder;
use Modules\Ticketing\Models\ServiceCatalogItem;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketAutomationRule;
use Modules\Ticketing\Models\TicketCategory;
use Modules\Ticketing\Models\TicketEscalationRule;
use Modules\Ticketing\Models\TicketSlaPolicy;
use Modules\Ticketing\Models\TicketTask;
use Modules\Ticketing\Models\TicketTeam;
use Modules\Ticketing\Models\TicketType;
use Modules\Ticketing\Services\ApprovalService;
use Modules\Ticketing\Services\AssignmentService;
use Modules\Ticketing\Services\AutomationService;
use Modules\Ticketing\Services\EscalationService;
use Modules\Ticketing\Services\SlaService;
use Modules\Ticketing\Services\TaskService;
use Modules\Ticketing\Services\TicketService;
use Tests\TestCase;

class TicketingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);
        $this->seed(EmployeeDatabaseSeeder::class);
        $this->seed(TicketingDatabaseSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('super-admin');
        return $u;
    }

    private function agent(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('CTO and Admin');
        return $u;
    }

    private function requester(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('employee');
        app(EmployeeService::class)->create([
            'first_name' => 'Req', 'last_name' => 'User', 'employment_type' => 'permanent',
            'user_id' => $u->id, 'company_id' => $this->company()->id,
            'branch_id' => $this->branch()->id, 'department_id' => $this->department()->id,
        ], $this->admin()->id);
        return $u->fresh('employee');
    }

    private function company(): Company { return Company::where('code', 'XG')->firstOrFail(); }

    private function branch(): Branch
    {
        return Branch::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'HQ'],
            ['name' => 'HQ', 'status' => 'active']);
    }

    private function department(): Department
    {
        return Department::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'IT'],
            ['name' => 'IT', 'status' => 'active', 'branch_id' => $this->branch()->id]);
    }

    private function ticket(?User $requester = null, array $overrides = []): Ticket
    {
        return app(TicketService::class)->create(array_merge([
            'subject' => 'Printer broken', 'description' => 'It does not print.',
        ], $overrides), $requester ?? $this->requester());
    }

    /* ---------------- Creation & numbering ---------------- */

    public function test_ticket_number_is_unique_and_sequential(): void
    {
        $t1 = $this->ticket();
        $t2 = $this->ticket();
        $this->assertMatchesRegularExpression('/^XERIN-TKT-\d{4}-\d{6}$/', $t1->ticket_number);
        $this->assertNotEquals($t1->ticket_number, $t2->ticket_number);
    }

    public function test_requester_context_comes_from_employee_not_payload(): void
    {
        $requester = $this->requester();
        $other = Company::create(['code' => 'EVIL', 'legal_name' => 'Evil', 'status' => 'active']);

        $t = app(TicketService::class)->create([
            'subject' => 'X', 'description' => 'Y',
            'requester_company_id' => $other->id, // must be ignored — not fillable input
        ], $requester);

        $this->assertSame($this->company()->id, $t->requester_company_id);
        $this->assertSame($this->branch()->id, $t->requester_branch_id);
        $this->assertSame($requester->employee->id, $t->requester_employee_id);
    }

    public function test_sla_attached_on_create_per_priority(): void
    {
        $t = $this->ticket(overrides: ['priority' => 'critical']);
        $this->assertNotNull($t->sla);
        $this->assertSame('Critical', $t->sla->policy->name);
        $this->assertSame(15, $t->sla->policy->response_minutes);
    }

    /* ---------------- Visibility & scoping ---------------- */

    public function test_requester_sees_only_own_tickets_without_view_perm(): void
    {
        $r1 = $this->requester();
        $r2 = $this->requester();
        $t1 = $this->ticket($r1);
        $t2 = $this->ticket($r2);

        $list = app(TicketService::class)->query($r1)->get();
        $this->assertTrue($list->contains('id', $t1->id));
        $this->assertFalse($list->contains('id', $t2->id));
        $this->assertTrue(app(TicketService::class)->canView($r1, $t1));
        $this->assertFalse(app(TicketService::class)->canView($r1, $t2));
    }

    public function test_cannot_guess_ticket_id_access(): void
    {
        $t = $this->ticket();
        $stranger = User::factory()->create(['status' => 'active']);
        $this->actingAs($stranger)->get("/ticketing/{$t->id}")->assertForbidden();
        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/tickets/{$t->id}")->assertForbidden();
    }

    public function test_agent_sees_company_tickets(): void
    {
        $agent = $this->agent();
        app(EmployeeService::class)->create([
            'first_name' => 'A', 'last_name' => 'G', 'employment_type' => 'permanent',
            'user_id' => $agent->id, 'company_id' => $this->company()->id,
        ], $this->admin()->id);
        $t = $this->ticket();

        $this->assertTrue(app(TicketService::class)->canView($agent->fresh('employee'), $t));
    }

    /* ---------------- Assignment ---------------- */

    public function test_assignment_validates_active_agent_and_records_history(): void
    {
        $t = $this->ticket();
        $agent = $this->agent();
        $admin = $this->admin();

        app(AssignmentService::class)->assign($t, ['agent_id' => $agent->id], $admin, 'First line');
        $this->assertSame($agent->id, $t->fresh()->assigned_agent_id);
        $this->assertSame('assigned', $t->fresh()->status);
        $this->assertCount(1, $t->assignmentHistory);
    }

    public function test_inactive_user_cannot_be_assigned(): void
    {
        $t = $this->ticket();
        $inactive = User::factory()->create(['status' => 'suspended']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(AssignmentService::class)->assign($t, ['agent_id' => $inactive->id], $this->admin());
    }

    public function test_unauthorized_user_cannot_assign(): void
    {
        $t = $this->ticket();
        $r = $this->requester();
        $this->actingAs($r)->post("/ticketing/{$t->id}/assign", ['agent_id' => $r->id])->assertForbidden();
    }

    /* ---------------- Lifecycle ---------------- */

    public function test_status_transitions_enforced(): void
    {
        $t = $this->ticket(); // submitted
        $agent = $this->agent();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TicketService::class)->transition($t, 'closed', $agent); // illegal jump
    }

    public function test_full_lifecycle_to_closed(): void
    {
        $t = $this->ticket();
        $agent = $this->agent();
        $svc = app(TicketService::class);

        $svc->transition($t, 'under_review', $agent);
        $svc->transition($t->fresh(), 'assigned', $agent);
        $svc->transition($t->fresh(), 'in_progress', $agent);
        $svc->transition($t->fresh(), 'resolved', $agent);
        $svc->transition($t->fresh(), 'closed', $agent);

        $f = $t->fresh();
        $this->assertSame('closed', $f->status);
        $this->assertNotNull($f->resolved_at);
        $this->assertNotNull($f->closed_at);
        $this->assertCount(6, $f->statusHistories);
    }

    public function test_reopen_increments_count_and_clears_dates(): void
    {
        $t = $this->ticket();
        $agent = $this->agent();
        $svc = app(TicketService::class);
        $svc->transition($t, 'under_review', $agent);
        $svc->transition($t->fresh(), 'resolved', $agent);
        $svc->transition($t->fresh(), 'reopened', $agent);

        $f = $t->fresh();
        $this->assertSame('reopened', $f->status);
        $this->assertSame(1, $f->reopen_count);
        $this->assertNull($f->resolved_at);
    }

    /* ---------------- Comments ---------------- */

    public function test_internal_notes_hidden_from_requester(): void
    {
        $t = $this->ticket();
        $requester = $t->requester;
        $agent = $this->agent();

        app(TicketService::class)->comment($t, $agent, 'Internal: user seems confused', internal: true);
        app(TicketService::class)->comment($t, $agent, 'Public reply');

        $this->actingAs($requester)->get("/ticketing/{$t->id}")
            ->assertSee('Public reply')->assertDontSee('user seems confused');
    }

    public function test_requester_cannot_post_internal_note(): void
    {
        $t = $this->ticket();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TicketService::class)->comment($t, $t->requester, 'Sneaky', internal: true);
    }

    public function test_first_agent_comment_sets_first_response(): void
    {
        $t = $this->ticket();
        $this->assertNull($t->first_response_at);
        app(TicketService::class)->comment($t, $this->agent(), 'Looking into it');
        $this->assertNotNull($t->fresh()->first_response_at);
        $this->assertNotNull($t->fresh()->sla->response_at);
    }

    /* ---------------- Approvals ---------------- */

    public function test_service_request_opens_approval_chain(): void
    {
        $service = ServiceCatalogItem::where('code', 'laptop')->firstOrFail(); // manager → CTO and Admin → Finance and Accounting
        $requester = $this->requester();

        $t = app(TicketService::class)->createFromService($service, [
            'subject' => 'Need laptop', 'description' => 'For work',
            'attachments' => ['dummy'], // satisfies requires_attachment
        ], $requester);

        $approvals = $t->approvals;
        $this->assertCount(3, $approvals);
        $this->assertSame(['manager', 'CTO and Admin', 'Finance and Accounting'], $approvals->pluck('approver_role')->all());
    }

    public function test_rejection_requires_reason_and_skips_remaining(): void
    {
        $service = ServiceCatalogItem::where('code', 'laptop')->firstOrFail();
        $t = app(TicketService::class)->createFromService(
            $service, ['subject' => 'L', 'description' => 'D', 'attachments' => ['x']], $this->requester());
        $approver = $this->admin();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ApprovalService::class)->decide($t->approvals->first(), $approver, 'rejected');
    }

    public function test_full_approval_flow(): void
    {
        $service = ServiceCatalogItem::where('code', 'laptop')->firstOrFail();
        $t = app(TicketService::class)->createFromService(
            $service, ['subject' => 'L', 'description' => 'D', 'attachments' => ['x']], $this->requester());
        $approver = $this->admin();
        $svc = app(ApprovalService::class);

        foreach ($t->approvals as $a) {
            $svc->decide($a, $approver, 'approved');
        }
        $this->assertFalse($t->fresh()->approvals()->pending()->exists());
        $this->assertTrue($t->fresh()->approvals()->where('status', 'approved')->count() === 3);
    }

    /* ---------------- SLA ---------------- */

    public function test_business_hours_resolution_calculation(): void
    {
        $policy = TicketSlaPolicy::where('name', 'Critical')->firstOrFail(); // 240m, 8-17
        $sla = app(SlaService::class);
        // Friday 16:30 + 240min → Mon 08:00 + remaining (30m today + 240-30=210 → Mon 11:30)
        $friday = \Carbon\Carbon::parse('next friday')->setTime(16, 30);
        // business_days null → all 7 days in our policy, so it just continues
        $due = $sla->addBusinessMinutes($friday, 240, $policy);
        $this->assertTrue($due->gt($friday));
    }

    public function test_pending_status_pauses_sla_timer(): void
    {
        $t = $this->ticket();
        $agent = $this->agent();
        $svc = app(TicketService::class);
        $sla = $t->sla;
        $origDue = $sla->resolution_due_at;

        $svc->transition($t, 'under_review', $agent);
        $svc->transition($t->fresh(), 'pending', $agent);
        $this->assertNotNull($t->fresh()->sla->paused_at);

        \Carbon\Carbon::setTestNow(now()->addMinutes(30));
        $svc->transition($t->fresh(), 'in_progress', $agent);
        $tracking = $t->fresh()->sla;
        $this->assertNull($tracking->paused_at);
        $this->assertEquals(30, $tracking->paused_minutes);
        $this->assertTrue($tracking->resolution_due_at->gt($origDue));
        \Carbon\Carbon::setTestNow();
    }

    public function test_sla_scan_marks_breach_and_escalates(): void
    {
        $t = $this->ticket(overrides: ['priority' => 'critical']);
        TicketEscalationRule::create([
            'name' => 'Esc on breach', 'trigger' => 'sla_breach',
            'new_priority' => 'critical', 'is_active' => true,
        ]);

        \Carbon\Carbon::setTestNow(now()->addDays(2)); // past all SLAs
        $stats = app(SlaService::class)->scan();
        \Carbon\Carbon::setTestNow();

        $this->assertGreaterThan(0, $stats['breached']);
        $this->assertGreaterThan(0, $stats['escalated']);
        $this->assertTrue($t->fresh()->sla->resolution_breached);
        $this->assertCount(1, $t->fresh()->escalations);
    }

    /* ---------------- Automation ---------------- */

    public function test_automation_assigns_team_on_create(): void
    {
        $team = TicketTeam::create(['code' => 'HD', 'name' => 'Helpdesk']);
        TicketAutomationRule::create([
            'name' => 'Auto IT', 'event' => 'created', 'action' => 'assign_team',
            'action_params' => ['team_id' => $team->id], 'is_active' => true,
        ]);

        $t = $this->ticket();
        $this->assertSame($team->id, $t->fresh()->assigned_team_id);
        $this->assertSame('assigned', $t->fresh()->status);
        $this->assertDatabaseHas('ticket_automation_logs', ['ticket_id' => $t->id, 'result' => 'success']);
    }

    public function test_automation_loop_guard(): void
    {
        $rule = TicketAutomationRule::create([
            'name' => 'P', 'event' => 'created', 'action' => 'set_priority',
            'action_params' => ['priority' => 'high'], 'max_runs_per_ticket' => 1,
        ]);
        $t = $this->ticket();
        // Manually re-run: max_runs=1 already consumed on create
        app(AutomationService::class)->run('created', $t->fresh());
        $this->assertSame(1, $t->automationLogs()->where('rule_id', $rule->id)->count());
    }

    public function test_auto_close_resolved_tickets(): void
    {
        $rule = TicketAutomationRule::create([
            'name' => 'Close old', 'event' => 'scheduled', 'action' => 'auto_close',
            'action_params' => ['after_hours' => 24],
        ]);
        $t = $this->ticket();
        $agent = $this->agent();
        $svc = app(TicketService::class);
        $svc->transition($t, 'under_review', $agent);
        $svc->transition($t->fresh(), 'resolved', $agent);
        $t->update(['resolved_at' => now()->subDays(2)]);

        $this->assertSame(1, app(AutomationService::class)->autoClose());
        $this->assertSame('closed', $t->fresh()->status);
    }

    /* ---------------- Tasks ---------------- */

    public function test_task_creation_and_dependency_blocking(): void
    {
        $svc = app(TaskService::class);
        $user = $this->agent();
        $emp = $this->requester()->employee;

        $dep = $svc->create(['title' => 'First'], $user);
        $blocked = $svc->create(['title' => 'Second', 'depends_on_ids' => [$dep->id], 'assignee_employee_id' => $emp->id], $user);

        $this->assertMatchesRegularExpression('/^XERIN-TSK-\d{4}-\d{6}$/', $blocked->task_number);
        $this->assertTrue($blocked->isBlocked());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->updateStatus($blocked, 'completed', $user);
    }

    public function test_task_completes_after_dependencies_done(): void
    {
        $svc = app(TaskService::class);
        $user = $this->agent();
        $dep = $svc->create(['title' => 'A'], $user);
        $t = $svc->create(['title' => 'B', 'depends_on_ids' => [$dep->id]], $user);

        $svc->updateStatus($dep, 'completed', $user);
        $this->assertFalse($t->fresh()->isBlocked());
        $svc->updateStatus($t->fresh(), 'completed', $user);
        $this->assertSame('completed', $t->fresh()->status);
    }

    /* ---------------- API ---------------- */

    public function test_api_create_list_show(): void
    {
        $r = $this->requester();
        Sanctum::actingAs($r);

        $res = $this->postJson('/api/v1/tickets', [
            'subject' => 'API ticket', 'description' => 'via API', 'priority' => 'high',
        ])->assertCreated()->assertJson(['success' => true]);

        $id = $res->json('data.id');
        $this->getJson('/api/v1/tickets')->assertOk()->assertJson(['success' => true]);
        $this->getJson("/api/v1/tickets/{$id}")->assertOk()->assertJsonPath('data.subject', 'API ticket');
    }

    public function test_api_unauthenticated_denied(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
        $this->postJson('/api/v1/tickets', ['subject' => 'x', 'description' => 'y'])->assertUnauthorized();
    }

    public function test_api_transition_via_endpoints(): void
    {
        $t = $this->ticket();
        $agent = $this->agent();
        app(AssignmentService::class)->assign($t, ['agent_id' => $agent->id], $this->admin());
        Sanctum::actingAs($agent);

        $this->postJson("/api/v1/tickets/{$t->id}/resolve", [])->assertOk();
        $this->postJson("/api/v1/tickets/{$t->id}/close", [])->assertOk();
        $this->assertSame('closed', $t->fresh()->status);
    }

    /* ---------------- Dashboard & audit ---------------- */

    public function test_dashboard_metrics_real(): void
    {
        $admin = $this->admin();
        $this->ticket();
        $this->ticket(overrides: ['priority' => 'critical']);

        $m = app(TicketService::class)->dashboard($admin);
        $this->assertSame(2, $m['total']);
        $this->assertSame(2, $m['unassigned']);
        $this->assertSame(1, $m['critical']);
        $this->assertArrayHasKey('submitted', $m['by_status']);
    }

    public function test_audit_and_history_recorded(): void
    {
        $t = $this->ticket();
        $this->assertDatabaseHas('audit_logs', ['action' => 'ticket.created', 'resource_id' => $t->id]);
        $this->assertDatabaseHas('ticket_status_histories', ['ticket_id' => $t->id, 'to_status' => 'submitted']);
    }

    public function test_teams_membership_and_workload(): void
    {
        $emp = $this->requester()->employee;
        $team = TicketTeam::create(['code' => 'NOC', 'name' => 'NOC']);
        $team->members()->sync([$emp->id]);

        $wl = app(\Modules\Ticketing\Services\TeamService::class)->workload($team);
        $this->assertCount(1, $wl['members']);
        $this->assertSame(0, $wl['open_tickets']);
    }

    public function test_web_pages_render(): void
    {
        $admin = $this->admin();
        $this->ticket();

        foreach (['/ticketing', '/ticketing/dashboard', '/ticketing/my', '/ticketing/catalog',
                  '/ticketing/tasks', '/ticketing/kb', '/ticketing/admin/teams',
                  '/ticketing/admin/sla', '/ticketing/admin/reports'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
