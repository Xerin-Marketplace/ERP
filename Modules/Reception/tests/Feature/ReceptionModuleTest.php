<?php

namespace Modules\Reception\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Database\Seeders\RolePermissionSeeder;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Employee\Models\Employee;
use Modules\Employee\Services\EmployeeService;
use Modules\HR\Database\Seeders\HRDatabaseSeeder;
use Modules\HR\Models\AttendanceRecord;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Reception\Services\ReceptionService;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketTeam;
use Modules\Ticketing\Models\TicketTeamMember;
use Tests\TestCase;

class ReceptionModuleTest extends TestCase
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
        $this->seed(HRDatabaseSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('super-admin');
        return $u;
    }

    private function receptionist(?int $branchId = null): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('receptionist');
        app(EmployeeService::class)->create([
            'first_name' => 'Re', 'last_name' => 'Ception', 'employment_type' => 'permanent',
            'user_id' => $u->id, 'company_id' => $this->company()->id,
            'branch_id' => $branchId ?? $this->branch()->id, 'department_id' => $this->department()->id,
        ], $this->admin()->id);
        return $u->fresh('employee');
    }

    private function employeeAt(Branch $branch): Employee
    {
        static $n = 0; $n++;
        $emp = app(EmployeeService::class)->create([
            'first_name' => 'Emp'.$n, 'last_name' => 'Test', 'employment_type' => 'permanent',
            'user_id' => null, 'company_id' => $this->company()->id,
            'branch_id' => $branch->id, 'department_id' => $this->departmentFor($branch)->id,
        ], $this->admin()->id);
        return $emp;
    }

    private function company(): Company { return Company::where('code', 'XG')->firstOrFail(); }
    private function branch(): Branch
    {
        return Branch::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'TZ'], ['name' => 'Tanzania', 'status' => 'active']);
    }
    private function dubai(): Branch
    {
        return Branch::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'DXB'], ['name' => 'Dubai', 'status' => 'active']);
    }
    private function department(): Department
    {
        return $this->departmentFor($this->branch());
    }
    private function departmentFor(Branch $b): Department
    {
        return Department::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'OPS-'.$b->code], ['name' => 'Ops '.$b->code, 'status' => 'active', 'branch_id' => $b->id]);
    }

    public function test_receptionist_can_view_dashboard(): void
    {
        $u = $this->receptionist();
        $this->actingAs($u)->get(route('reception.dashboard'))->assertOk();
    }

    public function test_employee_cannot_view_reception_dashboard(): void
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('employee');
        $this->actingAs($u)->get(route('reception.dashboard'))->assertForbidden();
    }

    public function test_kpis_reflect_real_attendance_and_tickets(): void
    {
        $emp = $this->employeeAt($this->branch());
        AttendanceRecord::forceCreate([
            'reference' => 'AR-1', 'employee_id' => $emp->id, 'company_id' => $this->company()->id,
            'branch_id' => $this->branch()->id, 'work_date' => today(),
            'clock_in_at' => now()->setTime(8, 30), 'status' => 'late', 'late_minutes' => 30,
        ]);

        $kpi = app(ReceptionService::class)->kpis($this->branch()->id);
        $this->assertSame(1, $kpi['present']);
        $this->assertSame(1, $kpi['late']);
    }

    public function test_receptionist_sees_only_own_branch_tickets(): void
    {
        $dxbEmp = $this->employeeAt($this->dubai());
        Ticket::forceCreate([
            'ticket_number' => 'IT-DXB-2026-000001', 'subject' => 'Dubai issue', 'description' => 'x',
            'status' => 'submitted', 'priority' => 'normal', 'requester_id' => $this->admin()->id,
            'requester_employee_id' => $dxbEmp->id, 'requester_branch_id' => $this->dubai()->id,
        ]);

        $u = $this->receptionist($this->branch()->id);
        $resp = $this->actingAs($u)->get(route('reception.tickets.index'));
        $resp->assertOk()->assertDontSee('IT-DXB-2026-000001');
    }

    public function test_receptionist_creates_ticket_on_behalf_with_branch_number(): void
    {
        $u = $this->receptionist($this->branch()->id);
        $emp = $this->employeeAt($this->branch());

        $resp = $this->actingAs($u)->post(route('reception.tickets.store'), [
            'employee_id' => $emp->id, 'subject' => 'Printer broken', 'description' => 'HP laser not printing',
            'priority' => 'normal',
        ]);

        $ticket = Ticket::where('requester_employee_id', $emp->id)->first();
        $this->assertNotNull($ticket);
        $this->assertMatchesRegularExpression('/^IT-TZ-\d{4}-\d{6}$/', $ticket->ticket_number);
        $this->assertSame($this->branch()->id, $ticket->requester_branch_id);
        $resp->assertRedirect(route('reception.tickets.show', $ticket));
    }

    public function test_receptionist_cannot_create_ticket_for_other_branch(): void
    {
        $u = $this->receptionist($this->branch()->id);
        $dxbEmp = $this->employeeAt($this->dubai());

        $this->actingAs($u)->post(route('reception.tickets.store'), [
            'employee_id' => $dxbEmp->id, 'subject' => 'Net down', 'description' => 'x', 'priority' => 'high',
        ])->assertForbidden();
    }

    public function test_technician_availability_listed(): void
    {
        $tech = $this->employeeAt($this->branch());
        $team = TicketTeam::create(['code' => 'IT-TZ', 'name' => 'IT Tanzania', 'branch_id' => $this->branch()->id]);
        TicketTeamMember::create(['team_id' => $team->id, 'employee_id' => $tech->id, 'availability' => 'busy']);

        $techs = app(ReceptionService::class)->technicians($this->branch()->id);
        $this->assertTrue($techs->contains('availability', 'busy'));
    }

    public function test_attendance_page_is_read_only_view(): void
    {
        $emp = $this->employeeAt($this->branch());
        AttendanceRecord::forceCreate([
            'reference' => 'AR-2', 'employee_id' => $emp->id, 'company_id' => $this->company()->id,
            'branch_id' => $this->branch()->id, 'work_date' => today(), 'status' => 'present',
            'clock_in_at' => now()->setTime(8, 0),
        ]);

        $u = $this->receptionist($this->branch()->id);
        $this->actingAs($u)->get(route('reception.attendance.index'))->assertOk()->assertSee('Present');
        $this->assertFalse($u->can('attendance.manage'));
        $this->assertFalse($u->can('hr.compensation.view'));
        $this->assertFalse($u->can('hr.bank_details.view'));
    }

    public function test_directory_shows_employees_without_sensitive_fields(): void
    {
        $this->employeeAt($this->branch());
        $u = $this->receptionist($this->branch()->id);
        $resp = $this->actingAs($u)->get(route('reception.directory'));
        $resp->assertOk()->assertDontSee('national_id')->assertDontSee('salary');
    }
}
