<?php

namespace Modules\HR\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Employee\Models\Employee;
use Modules\Employee\Services\EmployeeService;
use Modules\HR\Database\Seeders\HRDatabaseSeeder;
use Modules\HR\Models\AttendanceRecord;
use Modules\HR\Models\DisciplinaryCase;
use Modules\HR\Models\JobVacancy;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\PerformanceCycle;
use Modules\HR\Models\ShiftDefinition;
use Modules\HR\Models\TrainingProgram;
use Modules\HR\Services\AttendanceService;
use Modules\HR\Services\DisciplinaryService;
use Modules\HR\Services\HRDashboardService;
use Modules\HR\Services\LeaveService;
use Modules\HR\Services\OvertimeService;
use Modules\HR\Services\PerformanceService;
use Modules\HR\Services\RecruitmentService;
use Modules\HR\Services\ShiftService;
use Modules\HR\Services\TrainingService;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Tests\TestCase;

class HRModuleTest extends TestCase
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

    private function hrOfficer(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('CTO and Admin');
        return $u;
    }

    private function employeeUser(array $personal = []): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('employee');
        app(EmployeeService::class)->create(array_merge([
            'first_name' => 'Test', 'last_name' => 'Worker', 'employment_type' => 'permanent',
            'user_id' => $u->id, 'company_id' => $this->company()->id,
            'branch_id' => $this->branch()->id, 'department_id' => $this->department()->id,
        ], $personal), $this->admin()->id);
        return $u->fresh('employee');
    }

    private function company(): Company { return Company::where('code', 'XG')->firstOrFail(); }
    private function branch(): Branch
    {
        return Branch::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'HQ'], ['name' => 'HQ', 'status' => 'active']);
    }
    private function department(): Department
    {
        return Department::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'IT'], ['name' => 'IT', 'status' => 'active', 'branch_id' => $this->branch()->id]);
    }

    /* ================= ATTENDANCE ================= */

    public function test_clock_in_and_out_records_worked_minutes(): void
    {
        $u = $this->employeeUser();
        $svc = app(AttendanceService::class);

        $record = $svc->clockIn($u->employee, $u);
        $this->assertNotNull($record->clock_in_at);
        $this->assertSame('present', $record->status);

        Carbon::setTestNow(now()->addHours(8)->addMinutes(30));
        $out = $svc->clockOut($u->employee, $u);
        Carbon::setTestNow();

        $this->assertEquals(510, $out->worked_minutes);
        $this->assertCount(2, $out->events);
    }

    public function test_duplicate_clock_in_rejected(): void
    {
        $u = $this->employeeUser();
        $svc = app(AttendanceService::class);
        $svc->clockIn($u->employee, $u);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->clockIn($u->employee, $u);
    }

    public function test_late_detection_with_shift_grace(): void
    {
        $u = $this->employeeUser();
        $shift = ShiftDefinition::where('code', 'DAY')->firstOrFail(); // 08:00 + 15m grace
        app(ShiftService::class)->assign($shift, $u->employee, ['effective_from' => now()->subDay()], $this->admin());

        Carbon::setTestNow(now()->setTime(8, 45)); // 45m late
        $record = app(AttendanceService::class)->clockIn($u->employee, $u);
        Carbon::setTestNow();

        $this->assertSame(45, $record->late_minutes);
        $this->assertSame('late', $record->status);
    }

    public function test_overnight_shift_end_calculation(): void
    {
        $u = $this->employeeUser();
        $night = ShiftDefinition::where('code', 'NIGHT')->firstOrFail(); // 18:00–06:00 overnight
        $shiftSvc = app(ShiftService::class);
        $this->assertTrue($night->overnight);
        $this->assertSame(660, $shiftSvc->scheduledMinutes($night)); // 12h - 60m break
    }

    public function test_correction_flow(): void
    {
        $u = $this->employeeUser();
        $approver = $this->hrOfficer();
        $svc = app(AttendanceService::class);

        $c = $svc->requestCorrection($u->employee, [
            'work_date' => now()->toDateString(), 'type' => 'missing_clock_in',
            'requested_clock_in' => '08:05', 'reason' => 'Forgot to clock in',
        ]);
        $this->assertMatchesRegularExpression('/^XERIN-ACR-\d{4}-\d{6}$/', $c->reference);

        $svc->decideCorrection($c, $approver, 'approved');
        $record = AttendanceRecord::where('employee_id', $u->employee->id)->whereDate('work_date', now()->toDateString())->first();
        $this->assertSame('08:05', $record->clock_in_at->format('H:i'));
        $this->assertSame('corrected', $record->verification);
    }

    public function test_rejection_requires_reason(): void
    {
        $u = $this->employeeUser();
        $c = app(AttendanceService::class)->requestCorrection($u->employee, [
            'work_date' => now()->toDateString(), 'type' => 'other', 'reason' => 'x',
        ]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(AttendanceService::class)->decideCorrection($c, $this->hrOfficer(), 'rejected');
    }

    /* ================= SHIFTS ================= */

    public function test_assignment_ends_overlapping_shifts(): void
    {
        $u = $this->employeeUser();
        $day = ShiftDefinition::where('code', 'DAY')->first();
        $flex = ShiftDefinition::where('code', 'FLEX')->first();
        $svc = app(ShiftService::class);

        $a1 = $svc->assign($day, $u->employee, ['effective_from' => now()->subDays(10)], $this->admin());
        $a2 = $svc->assign($flex, $u->employee, ['effective_from' => now()], $this->admin());

        $this->assertSame('ended', $a1->fresh()->status);
        $this->assertSame($flex->id, app(AttendanceService::class)->shiftFor($u->employee, now())->id);
    }

    /* ================= LEAVE ================= */

    public function test_balances_seeded_per_eligibility(): void
    {
        $male = $this->employeeUser(['gender' => 'male'])->employee;
        $female = $this->employeeUser(['gender' => 'female'])->employee;
        $svc = app(LeaveService::class);

        $svc->ensureBalances($male, 2026);
        $svc->ensureBalances($female, 2026);

        $ml = LeaveType::where('code', 'ML')->first();
        $pl = LeaveType::where('code', 'PL')->first();

        $this->assertEquals(0, $svc->balance($male, $ml, 2026)->entitled);
        $this->assertEquals(84, $svc->balance($female, $ml, 2026)->entitled);
        $this->assertEquals(3, $svc->balance($male, $pl, 2026)->entitled);
        $this->assertEquals(0, $svc->balance($female, $pl, 2026)->entitled);
    }

    public function test_leave_request_reserves_balance_and_working_days(): void
    {
        $u = $this->employeeUser();
        $svc = app(LeaveService::class);
        $al = LeaveType::where('code', 'AL')->first(); // 28d

        $nextMonday = Carbon::parse('next monday');
        $req = $svc->request($u->employee, [
            'leave_type_id' => $al->id,
            'start_date' => $nextMonday->toDateString(),
            'end_date' => $nextMonday->copy()->addDays(4)->toDateString(), // Mon–Fri = 5 working days
        ], $u);

        $this->assertEquals(5, (float) $req->days);
        $bal = $svc->balance($u->employee, $al, (int) $req->start_date->format('Y'));
        $this->assertEquals(5, (float) $bal->pending);
        $this->assertEquals(23, $bal->available);
        $this->assertMatchesRegularExpression('/^XERIN-LEV-\d{4}-\d{6}$/', $req->reference);
    }

    public function test_insufficient_balance_rejected(): void
    {
        $u = $this->employeeUser();
        $svc = app(LeaveService::class);
        $al = LeaveType::where('code', 'AL')->first();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->request($u->employee, [
            'leave_type_id' => $al->id,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->addDays(60)->toDateString(), // way over 28
        ], $u);
    }

    public function test_overlapping_leave_blocked(): void
    {
        $u = $this->employeeUser();
        $svc = app(LeaveService::class);
        $al = LeaveType::where('code', 'AL')->first();
        $d = now()->addWeek();

        $svc->request($u->employee, ['leave_type_id' => $al->id, 'start_date' => $d->toDateString(), 'end_date' => $d->copy()->addDays(3)->toDateString()], $u);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->request($u->employee, ['leave_type_id' => $al->id, 'start_date' => $d->copy()->addDay()->toDateString(), 'end_date' => $d->copy()->addDays(5)->toDateString()], $u);
    }

    public function test_approval_consumes_balance(): void
    {
        $u = $this->employeeUser();
        $approver = $this->hrOfficer();
        $svc = app(LeaveService::class);
        $al = LeaveType::where('code', 'AL')->first();

        $req = $svc->request($u->employee, ['leave_type_id' => $al->id,
            'start_date' => Carbon::parse('next monday')->toDateString(),
            'end_date' => Carbon::parse('next monday')->addDays(1)->toDateString()], $u); // 2d

        $svc->decide($req, $approver, 'approved');
        $bal = $svc->balance($u->employee, $al, (int) $req->start_date->format('Y'));

        $this->assertSame('approved', $req->fresh()->status);
        $this->assertEquals(2, (float) $bal->used);
        $this->assertEquals(0, (float) $bal->pending);
    }

    public function test_multi_step_approval_chain(): void
    {
        $u = $this->employeeUser();
        $approver = $this->hrOfficer();
        $svc = app(LeaveService::class);
        $upl = LeaveType::where('code', 'UPL')->first(); // manager → hr_manager

        $req = $svc->request($u->employee, ['leave_type_id' => $upl->id,
            'start_date' => Carbon::parse('next monday')->toDateString(),
            'end_date' => Carbon::parse('next monday')->addDay()->toDateString()], $u);

        $this->assertCount(2, $req->approvals);
        $svc->decide($req, $approver, 'approved'); // step 1
        $this->assertSame('pending', $req->fresh()->status); // still pending step 2
        $svc->decide($req->fresh(), $approver, 'approved'); // step 2
        $this->assertSame('approved', $req->fresh()->status);
    }

    public function test_self_approval_blocked(): void
    {
        $u = $this->employeeUser();
        $u->givePermissionTo('leave.approve'); // even with permission, own request blocked
        $svc = app(LeaveService::class);
        $al = LeaveType::where('code', 'AL')->first();

        $req = $svc->request($u->employee, ['leave_type_id' => $al->id,
            'start_date' => Carbon::parse('next monday')->toDateString(),
            'end_date' => Carbon::parse('next monday')->toDateString()], $u);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->decide($req, $u, 'approved');
    }

    public function test_rejection_releases_balance(): void
    {
        $u = $this->employeeUser();
        $svc = app(LeaveService::class);
        $al = LeaveType::where('code', 'AL')->first();

        $req = $svc->request($u->employee, ['leave_type_id' => $al->id,
            'start_date' => Carbon::parse('next monday')->toDateString(),
            'end_date' => Carbon::parse('next monday')->addDays(2)->toDateString()], $u);

        $svc->decide($req, $this->hrOfficer(), 'rejected', 'Coverage needed');
        $bal = $svc->balance($u->employee, $al, (int) $req->start_date->format('Y'));

        $this->assertSame('rejected', $req->fresh()->status);
        $this->assertEquals(0, (float) $bal->pending);
        $this->assertEquals(28, $bal->available);
    }

    /* ================= OVERTIME ================= */

    public function test_overtime_two_step_approval_and_verify(): void
    {
        $u = $this->employeeUser();
        $approver = $this->hrOfficer();
        $svc = app(OvertimeService::class);

        $ot = $svc->request($u->employee, ['work_date' => now()->toDateString(), 'planned_hours' => 4, 'reason' => 'Deadline']);
        $this->assertCount(2, $ot->approvals);

        $svc->decide($ot, $approver, 'approved');
        $svc->decide($ot->fresh(), $approver, 'approved');
        $this->assertSame('approved', $ot->fresh()->status);

        $svc->verify($ot->fresh(), $approver, 4.5);
        $this->assertSame('verified', $ot->fresh()->status);
        $this->assertEquals(4.5, (float) $ot->fresh()->actual_hours);
    }

    public function test_self_overtime_approval_blocked(): void
    {
        $u = $this->employeeUser();
        $u->givePermissionTo('overtime.approve');
        $svc = app(OvertimeService::class);
        $ot = $svc->request($u->employee, ['work_date' => now()->toDateString(), 'planned_hours' => 2, 'reason' => 'x']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->decide($ot, $u, 'approved');
    }

    /* ================= RECRUITMENT ================= */

    public function test_recruitment_full_workflow(): void
    {
        $hr = $this->hrOfficer();
        $admin = $this->admin();
        $svc = app(RecruitmentService::class);

        $req = $svc->requisition([
            'title' => 'Software Engineer', 'company_id' => $this->company()->id,
            'department_id' => $this->department()->id, 'positions' => 2,
        ], $hr);
        $this->assertMatchesRegularExpression('/^XERIN-REQ-\d{4}-\d{6}$/', $req->reference);

        $admin->givePermissionTo('recruitment.approve');
        $svc->decideRequisition($req, $admin, 'approved', null);

        $vacancy = $svc->publishVacancy(['title' => 'Software Engineer', 'description' => 'Build things'], $req->fresh());
        $app = $svc->apply($vacancy, ['full_name' => 'Jane Doe', 'email' => 'jane@x.com']);

        $svc->moveStage($app, 'shortlisted', $hr);
        $this->assertSame('shortlisted', $app->fresh()->stage);

        $iv = $svc->scheduleInterview($app->fresh(), ['scheduled_at' => now()->addDay(), 'mode' => 'video'], $hr);
        $svc->recordAssessment($iv, 8, 'Strong candidate', 'pass', $hr);
        $this->assertSame('pass', $iv->fresh()->outcome);
        $this->assertSame('interview', $app->fresh()->stage);
    }

    /* ================= PERFORMANCE ================= */

    public function test_self_review_only_self(): void
    {
        $u1 = $this->employeeUser();
        $u2 = $this->employeeUser();
        $cycle = app(PerformanceService::class)->createCycle(['name' => 'FY26', 'starts_at' => now()->startOfYear(), 'ends_at' => now()->endOfYear()]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(PerformanceService::class)->review($cycle, $u1->employee, ['kind' => 'self', 'summary' => 'x'], $u2);
    }

    public function test_manager_review_and_rating(): void
    {
        $u = $this->employeeUser();
        $manager = $this->hrOfficer();
        $manager->givePermissionTo('performance.assess');
        $svc = app(PerformanceService::class);
        $cycle = $svc->createCycle(['name' => 'FY26', 'starts_at' => now()->startOfYear(), 'ends_at' => now()->endOfYear()]);

        $review = $svc->review($cycle, $u->employee, ['kind' => 'manager', 'rating' => 4, 'summary' => 'Solid'], $manager);
        $this->assertMatchesRegularExpression('/^XERIN-PRF-\d{4}-\d{6}$/', $review->reference);
        $this->assertSame(4, $review->rating);
    }

    /* ================= TRAINING ================= */

    public function test_capacity_enforced_and_certification_issued(): void
    {
        $hr = $this->hrOfficer();
        $hr->givePermissionTo('training.manage');
        $svc = app(TrainingService::class);
        $admin = $this->admin();

        $program = $svc->createProgram([
            'code' => 'SEC1', 'title' => 'Security Basics', 'capacity' => 1,
            'certifies' => true, 'cert_validity_months' => 24, 'status' => 'open',
        ]);

        $e1 = $this->employeeUser()->employee;
        $e2 = $this->employeeUser()->employee;
        $enroll = $svc->enroll($program, $e1, $admin);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->enroll($program, $e2, $admin);
    }

    public function test_certification_on_completion(): void
    {
        $admin = $this->admin();
        $svc = app(TrainingService::class);
        $program = $svc->createProgram(['code' => 'SEC2', 'title' => 'Sec', 'certifies' => true, 'cert_validity_months' => 12]);
        $enroll = $svc->enroll($program, $this->employeeUser()->employee, $admin);

        $svc->complete($enroll, 95, $admin);
        $this->assertDatabaseHas('training_certifications', ['employee_id' => $enroll->employee_id, 'title' => 'Sec']);
    }

    /* ================= DISCIPLINARY ================= */

    public function test_case_confidential_to_ordinary_employee(): void
    {
        $u = $this->employeeUser();
        $case = $this->makeCase();
        $this->actingAs($u)->get("/hr/disciplinary/{$case->id}")->assertForbidden();
        $this->actingAs($u)->get('/hr/disciplinary')->assertForbidden();
    }

    public function test_case_lifecycle(): void
    {
        $admin = $this->admin();
        $svc = app(DisciplinaryService::class);
        $target = $this->employeeUser()->employee;

        $case = $svc->openCase($target, ['category' => 'absenteeism', 'incident' => 'No-show 3 days', 'severity' => 'moderate'], $admin);
        $this->assertMatchesRegularExpression('/^XERIN-CAS-\d{4}-\d{6}$/', $case->reference);

        $svc->recordResponse($case, 'Was sick', $admin);
        $svc->addAction($case, ['type' => 'written_warning', 'detail' => 'First warning'], $admin);
        $svc->advanceStatus($case, 'closed', $admin);

        $this->assertSame('closed', $case->fresh()->status);
        $this->assertCount(4, $case->fresh()->actions);
    }

    private function makeCase(): DisciplinaryCase
    {
        return app(DisciplinaryService::class)->openCase(
            $this->employeeUser()->employee,
            ['category' => 'misconduct', 'incident' => 'Test', 'severity' => 'minor'],
            $this->admin()
        );
    }

    /* ================= DASHBOARD & ISOLATION ================= */

    public function test_dashboard_metrics_real(): void
    {
        $u = $this->employeeUser();
        app(AttendanceService::class)->clockIn($u->employee, $u);

        $m = app(HRDashboardService::class)->for($this->admin());
        $this->assertSame(1, $m['attendance_today']['present']);
        $this->assertGreaterThanOrEqual(1, $m['employees']['total']);
    }

    public function test_ordinary_employee_sees_own_attendance_only(): void
    {
        $u1 = $this->employeeUser();
        $u2 = $this->employeeUser();
        $svc = app(AttendanceService::class);
        $svc->clockIn($u1->employee, $u1);
        $svc->clockIn($u2->employee, $u2);

        $list = $svc->query($u1)->get();
        $this->assertCount(1, $list);
        $this->assertSame($u1->employee->id, $list->first()->employee_id);
    }

    /* ================= API ================= */

    public function test_api_clock_in_out(): void
    {
        $u = $this->employeeUser();
        Sanctum::actingAs($u);

        $this->postJson('/api/v1/hr/attendance/clock-in')->assertCreated();
        $this->postJson('/api/v1/hr/attendance/clock-in')->assertStatus(422); // duplicate
        $this->postJson('/api/v1/hr/attendance/clock-out')->assertOk();
    }

    public function test_api_leave_request_and_balances(): void
    {
        $u = $this->employeeUser();
        Sanctum::actingAs($u);
        $al = LeaveType::where('code', 'AL')->first();

        $this->postJson('/api/v1/hr/leave/request', [
            'leave_type_id' => $al->id,
            'start_date' => Carbon::parse('next monday')->toDateString(),
            'end_date' => Carbon::parse('next monday')->addDay()->toDateString(),
        ])->assertCreated();

        $this->getJson('/api/v1/hr/leave/balances')->assertOk()
            ->assertJsonPath('data.0.type.code', 'AL');
    }

    public function test_api_unauthenticated_denied(): void
    {
        $this->getJson('/api/v1/hr/dashboard')->assertUnauthorized();
        $this->postJson('/api/v1/hr/attendance/clock-in')->assertUnauthorized();
    }

    public function test_api_dashboard_requires_permission(): void
    {
        $u = $this->employeeUser();
        Sanctum::actingAs($u);
        $this->getJson('/api/v1/hr/dashboard')->assertForbidden();
    }

    public function test_web_pages_render(): void
    {
        $admin = $this->admin();
        $this->actingAs($this->employeeUser())->get('/hr/me')->assertOk();
        foreach (['/hr/dashboard', '/hr/attendance', '/hr/leave', '/hr/leave/approvals',
                  '/hr/shifts', '/hr/overtime', '/hr/recruitment', '/hr/performance',
                  '/hr/training', '/hr/disciplinary', '/hr/welfare', '/hr/reports'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    /* ================= BANK DETAILS (Phase HR-04) ================= */

    private function emp(): Employee
    {
        return $this->employeeUser()->employee;
    }

    public function test_bank_account_saved_encrypted_and_masked(): void
    {
        $admin = $this->admin();
        $e = $this->emp();

        $this->actingAs($admin)->post(route('hr.bank.store', $e), [
            'account_holder_name' => 'Test Worker', 'bank_name' => 'NMB',
            'account_number' => '12345678901', 'currency' => 'TZS',
            'country' => 'TZ', 'payment_method' => 'bank_transfer',
            'payment_frequency' => 'monthly',
        ])->assertSessionHas('success');

        $acc = $e->bankAccounts()->sole();
        $raw = \Illuminate\Support\Facades\DB::table('employee_bank_accounts')->find($acc->id);
        $this->assertNotSame('12345678901', $raw->account_number); // encrypted
        $this->assertSame('****8901', $acc->maskedAccount());
        $this->assertSame('pending', $acc->verification_status);
    }

    public function test_bank_verify_requires_permission(): void
    {
        $e = $this->emp();
        $acc = $e->bankAccounts()->create([
            'account_holder_name' => 'T', 'bank_name' => 'NMB', 'account_number' => '999',
            'currency' => 'TZS', 'payment_method' => 'bank_transfer',
        ]);

        $officer = User::factory()->create(['status' => 'active']);
        $officer->assignRole('employee');
        $this->actingAs($officer)->post(route('hr.bank.verify', $acc), ['approve' => 1])->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('hr.bank.verify', $acc), ['approve' => 1]);
        $this->assertSame('verified', $acc->fresh()->verification_status);
    }

    /* ================= COMPENSATION (Phase HR-05) ================= */

    public function test_compensation_revision_workflow_supersedes_previous(): void
    {
        $admin = $this->admin();
        $approver = User::factory()->create(['status' => 'active']);
        $approver->assignRole('CTO and Admin');
        $e = $this->emp();

        $rev1 = app(\Modules\HR\Services\CompensationService::class)
            ->propose($e, ['basic_salary' => 500000, 'currency' => 'TZS',
                'pay_frequency' => 'monthly', 'effective_date' => now()->toDateString(),
                'reason' => 'Initial'], $admin);
        app(\Modules\HR\Services\CompensationService::class)->approve($rev1, $approver);

        $rev2 = app(\Modules\HR\Services\CompensationService::class)
            ->propose($e, ['basic_salary' => 650000, 'currency' => 'TZS',
                'pay_frequency' => 'monthly', 'effective_date' => now()->toDateString(),
                'reason' => 'Promotion'], $admin);
        app(\Modules\HR\Services\CompensationService::class)->approve($rev2, $approver);

        $this->assertSame('superseded', $rev1->fresh()->status);
        $this->assertSame($rev1->id, $rev2->fresh()->supersedes_id);
        $this->assertDatabaseHas('employee_events', ['employee_id' => $e->id, 'type' => 'salary_revision']);
    }

    public function test_requester_cannot_approve_own_salary_change(): void
    {
        $admin = $this->admin();
        $e = $this->emp();
        $rev = app(\Modules\HR\Services\CompensationService::class)
            ->propose($e, ['basic_salary' => 100, 'currency' => 'TZS',
                'pay_frequency' => 'monthly', 'effective_date' => now()->toDateString(),
                'reason' => 'x'], $admin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(\Modules\HR\Services\CompensationService::class)->approve($rev, $admin);
    }

    /* ================= PAYROLL (Phase HR-06) ================= */

    private function compedEmployee(): Employee
    {
        $admin = $this->admin();
        $approver = User::factory()->create(['status' => 'active']);
        $approver->assignRole('CTO and Admin');
        $e = $this->emp();
        $e->update(['status' => 'active']);
        $rev = app(\Modules\HR\Services\CompensationService::class)
            ->propose($e, ['basic_salary' => 1000000, 'housing_allowance' => 200000,
                'pension_deduction' => 50000, 'currency' => 'TZS',
                'pay_frequency' => 'monthly', 'effective_date' => now()->toDateString(),
                'reason' => 'Setup'], $admin);
        app(\Modules\HR\Services\CompensationService::class)->approve($rev, $approver);
        return $e;
    }

    public function test_payroll_process_approve_pay_cycle(): void
    {
        $admin = $this->admin();
        $e = $this->compedEmployee();
        $svc = app(\Modules\HR\Services\PayrollService::class);

        $period = $svc->openPeriod([
            'name' => '2026-02', 'start_date' => '2026-02-01', 'end_date' => '2026-02-28',
        ], $admin);

        $count = $svc->process($period, $admin);
        $this->assertSame(1, $count);

        $run = $period->runs()->sole();
        $this->assertSame('1200000.00', $run->gross_amount);
        $this->assertSame('50000.00', $run->total_deductions);
        $this->assertSame('1150000.00', $run->net_amount);
        $this->assertArrayHasKey('basic_salary', $run->breakdown);

        $svc->approve($period, $admin);
        $this->assertSame('approved', $run->fresh()->status);
        $this->assertSame('approved', $period->fresh()->status);

        $svc->markPaid($run->fresh(), $admin);
        $this->assertSame('paid', $run->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'hr.payroll.paid']);
    }

    public function test_payroll_run_unique_per_employee_per_period(): void
    {
        $admin = $this->admin();
        $this->compedEmployee();
        $svc = app(\Modules\HR\Services\PayrollService::class);
        $period = $svc->openPeriod(['name' => '2026-03', 'start_date' => '2026-03-01', 'end_date' => '2026-03-31'], $admin);

        $svc->process($period, $admin);
        $count2 = $svc->process($period, $admin); // reprocess — no dupes
        $this->assertSame(0, $count2);
        $this->assertSame(1, $period->runs()->count());
    }

    public function test_payroll_pages_accessible_to_admin(): void
    {
        $admin = $this->admin();
        foreach (['/hr/compensation', '/hr/payroll'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
