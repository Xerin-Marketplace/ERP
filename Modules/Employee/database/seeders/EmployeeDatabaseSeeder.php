<?php

namespace Modules\Employee\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\DocumentCategory;
use Modules\Employee\Models\JobGrade;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EmployeeDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'employee.view', 'employee.view_all', 'employee.create', 'employee.update',
        'employee.archive', 'employee.export',
        'employee.approve', 'employee.suspend', 'employee.terminate',
        'employee.profile.view', 'employee.profile.update', 'employee.profile.update_own',
        'employee.document.view', 'employee.document.upload', 'employee.document.delete',
        'employee.position.manage', 'employee.grade.manage',
        'employee.transfer.request', 'employee.transfer.approve',
        'employee.promotion.request', 'employee.promotion.approve',
        'employee.onboarding.manage', 'employee.offboarding.manage',
        'employee.history.view', 'employee.reports.view',
    ];

    public const ROLES = [
        'it-administrator' => self::PERMISSIONS,
        'group-administrator' => [
            'employee.view_all', 'employee.create', 'employee.update', 'employee.export',
            'employee.approve', 'employee.suspend', 'employee.terminate',
            'employee.profile.view', 'employee.profile.update',
            'employee.document.view', 'employee.document.upload', 'employee.document.delete',
            'employee.position.manage', 'employee.grade.manage',
            'employee.transfer.request', 'employee.transfer.approve',
            'employee.promotion.request', 'employee.promotion.approve',
            'employee.onboarding.manage', 'employee.offboarding.manage',
            'employee.history.view', 'employee.reports.view',
        ],
        'company-administrator' => [
            'employee.view', 'employee.create', 'employee.update', 'employee.export',
            'employee.suspend',
            'employee.profile.view', 'employee.profile.update',
            'employee.document.view', 'employee.document.upload',
            'employee.transfer.request', 'employee.promotion.request',
            'employee.onboarding.manage', 'employee.offboarding.manage',
            'employee.history.view', 'employee.reports.view',
        ],
        'branch-manager' => [
            'employee.view', 'employee.profile.view', 'employee.document.view',
            'employee.transfer.request', 'employee.history.view',
        ],
        'department-manager' => [
            'employee.view', 'employee.profile.view', 'employee.history.view',
        ],
        'employee' => ['employee.profile.update_own'],
        'auditor' => [
            'employee.view_all', 'employee.profile.view', 'employee.document.view',
            'employee.history.view', 'employee.reports.view',
        ],
        'management-viewer' => ['employee.view_all', 'employee.reports.view'],
        'it-officer' => ['employee.view', 'employee.profile.view'],
    ];

    public const DOCUMENT_CATEGORIES = [
        ['code' => 'employment_contract', 'name' => 'Employment Contract', 'is_confidential' => true, 'requires_expiry' => false],
        ['code' => 'appointment_letter', 'name' => 'Appointment Letter', 'is_confidential' => true, 'requires_expiry' => false],
        ['code' => 'identification', 'name' => 'Identification Document', 'is_confidential' => true, 'requires_expiry' => true],
        ['code' => 'academic_certificate', 'name' => 'Academic Certificate', 'is_confidential' => false, 'requires_expiry' => false],
        ['code' => 'professional_certificate', 'name' => 'Professional Certificate', 'is_confidential' => false, 'requires_expiry' => true],
        ['code' => 'training_certificate', 'name' => 'Training Certificate', 'is_confidential' => false, 'requires_expiry' => false],
        ['code' => 'disciplinary', 'name' => 'Disciplinary Document', 'is_confidential' => true, 'requires_expiry' => false],
        ['code' => 'promotion_letter', 'name' => 'Promotion Letter', 'is_confidential' => true, 'requires_expiry' => false],
        ['code' => 'transfer_letter', 'name' => 'Transfer Letter', 'is_confidential' => true, 'requires_expiry' => false],
        ['code' => 'termination', 'name' => 'Termination Document', 'is_confidential' => true, 'requires_expiry' => false],
        ['code' => 'other', 'name' => 'Other', 'is_confidential' => false, 'requires_expiry' => false],
    ];

    public const GRADES = [
        ['code' => 'G1', 'name' => 'Entry Level', 'level' => 1],
        ['code' => 'G2', 'name' => 'Junior', 'level' => 2],
        ['code' => 'G3', 'name' => 'Intermediate', 'level' => 3],
        ['code' => 'G4', 'name' => 'Senior', 'level' => 4],
        ['code' => 'G5', 'name' => 'Lead', 'level' => 5],
        ['code' => 'G6', 'name' => 'Manager', 'level' => 6],
        ['code' => 'G7', 'name' => 'Senior Manager', 'level' => 7],
        ['code' => 'G8', 'name' => 'Director', 'level' => 8],
        ['code' => 'G9', 'name' => 'Executive', 'level' => 9],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::PERMISSIONS as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach (self::ROLES as $roleName => $permissions) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                if ($permissions) {
                    $role->givePermissionTo($permissions);
                }
            }

            if ($super = Role::where('name', 'super-admin')->first()) {
                $super->givePermissionTo(self::PERMISSIONS);
            }

            foreach (self::DOCUMENT_CATEGORIES as $cat) {
                DocumentCategory::firstOrCreate(['code' => $cat['code']], $cat);
            }
            foreach (self::GRADES as $grade) {
                JobGrade::firstOrCreate(['code' => $grade['code']], $grade);
            }
        });
    }
}
