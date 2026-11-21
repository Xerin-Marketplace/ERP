<?php

namespace Modules\Reception\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Reception\Models\AccessWorkflow;
use Modules\Reception\Models\AccessWorkflowVersion;
use Modules\Reception\Models\AccessWorkflowStage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ReceptionDatabaseSeeder extends Seeder
{
    public const ACCESS_PERMISSIONS = [
        'reception.access.view',
        'reception.access.review',
        'reception.access.verify',
        'reception.access.route',
        'reception.access.provision',
        'reception.access.workflows.manage',
        'reception.access.audit.view',
    ];

    public function run(): void
    {
        foreach (self::ACCESS_PERMISSIONS as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Reception may receive, verify and route — never provision or manage workflows.
        $receptionist = Role::firstOrCreate(['name' => 'Customer Care', 'guard_name' => 'web']);
        $receptionist->givePermissionTo([
            'reception.access.view', 'reception.access.review', 'reception.access.verify',
            'reception.access.route', 'reception.access.audit.view',
        ]);
        // Provisioning + workflow admin live with IT administration, not reception.
        Role::firstOrCreate(['name' => 'CTO and Admin', 'guard_name' => 'web'])->givePermissionTo(self::ACCESS_PERMISSIONS);
        Role::firstOrCreate(['name' => 'CTO and Admin', 'guard_name' => 'web'])->givePermissionTo(self::ACCESS_PERMISSIONS);

        $this->seedDefaultWorkflows();
    }

    private function seedDefaultWorkflows(): void
    {
        $defs = [
            ['name' => 'New Employee Access', 'request_type' => 'new_employee',
             'stages' => [
                 ['name' => 'Department Manager', 'approver_role' => 'Branch Manager', 'sla_hours' => 48],
                 ['name' => 'HR Confirmation', 'approver_role' => 'CTO and Admin', 'sla_hours' => 48],
                 ['name' => 'IT Approval', 'approver_role' => 'CTO and Admin', 'sla_hours' => 72],
             ]],
            ['name' => 'Existing Employee Access Change', 'request_type' => 'existing_employee',
             'stages' => [
                 ['name' => 'Department Manager', 'approver_role' => 'Branch Manager', 'sla_hours' => 48],
                 ['name' => 'IT Approval', 'approver_role' => 'CTO and Admin', 'sla_hours' => 72],
             ]],
            ['name' => 'External Access', 'request_type' => 'external',
             'stages' => [
                 ['name' => 'Sponsor Manager', 'approver_role' => 'Branch Manager', 'sla_hours' => 24],
                 ['name' => 'IT Security', 'approver_role' => 'CTO and Admin', 'sla_hours' => 48],
             ]],
        ];

        foreach ($defs as $def) {
            $w = AccessWorkflow::firstOrCreate(
                ['name' => $def['name']],
                ['request_type' => $def['request_type'], 'current_version' => 0, 'is_active' => true]
            );
            if ($w->current_version === 0) {
                $v = AccessWorkflowVersion::create([
                    'workflow_id' => $w->id, 'version' => 1,
                    'status' => 'published', 'effective_from' => now()->toDateString(),
                ]);
                foreach (array_values($def['stages']) as $i => $s) {
                    AccessWorkflowStage::create([
                        'version_id' => $v->id, 'sequence' => $i + 1,
                        'name' => $s['name'], 'approver_role' => $s['approver_role'],
                        'sla_hours' => $s['sla_hours'], 'required' => true,
                    ]);
                }
                $w->update(['current_version' => 1]);
            }
        }
    }
}
