<?php

namespace Modules\Ticketing\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Ticketing\Models\ServiceCatalogItem;
use Modules\Ticketing\Models\TicketCategory;
use Modules\Ticketing\Models\TicketSlaPolicy;
use Modules\Ticketing\Models\TicketType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TicketingDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'ticket.view', 'ticket.view_all', 'ticket.create', 'ticket.update',
        'ticket.assign', 'ticket.close', 'ticket.reopen', 'ticket.delete',
        'ticket.comment', 'ticket.internal_note', 'ticket.attachment.upload',
        'ticket.approve',
        'task.view', 'task.create', 'task.assign', 'task.update', 'task.complete',
        'sla.manage', 'escalation.manage', 'service_catalog.manage',
        'ticket_automation.manage', 'ticket_reports.view', 'knowledge_base.manage',
    ];

    public const ROLES = [
        'it-administrator' => self::PERMISSIONS,
        'it-officer' => [
            'ticket.view', 'ticket.create', 'ticket.update', 'ticket.assign',
            'ticket.close', 'ticket.reopen', 'ticket.comment', 'ticket.internal_note',
            'ticket.attachment.upload', 'ticket.approve',
            'task.view', 'task.create', 'task.assign', 'task.update', 'task.complete',
            'knowledge_base.manage', 'service_catalog.manage',
        ],
        'group-administrator' => [
            'ticket.view_all', 'ticket.create', 'ticket.assign', 'ticket.approve',
            'ticket.comment', 'ticket.internal_note', 'sla.manage', 'escalation.manage',
            'service_catalog.manage', 'ticket_automation.manage', 'ticket_reports.view',
            'knowledge_base.manage',
            'task.view', 'task.create', 'task.assign', 'task.update', 'task.complete',
        ],
        'company-administrator' => [
            'ticket.view', 'ticket.create', 'ticket.assign', 'ticket.approve',
            'ticket.comment', 'ticket.internal_note', 'ticket_reports.view',
            'task.view', 'task.create', 'task.assign', 'task.update',
        ],
        'branch-manager' => [
            'ticket.view', 'ticket.create', 'ticket.comment', 'ticket.approve',
            'task.view', 'task.create', 'task.assign',
        ],
        'department-manager' => [
            'ticket.view', 'ticket.create', 'ticket.comment', 'ticket.approve',
            'task.view', 'task.create', 'task.assign', 'task.update',
        ],
        'employee' => [
            'ticket.create', 'ticket.comment', 'ticket.attachment.upload',
            'task.view', 'task.update',
        ],
        'auditor' => ['ticket.view_all', 'ticket_reports.view', 'task.view'],
        'management-viewer' => ['ticket.view_all', 'ticket_reports.view', 'task.view'],
    ];

    public const TYPES = [
        ['code' => 'it_support', 'name' => 'IT Support Request'],
        ['code' => 'technical_issue', 'name' => 'Technical Issue'],
        ['code' => 'hardware', 'name' => 'Hardware Request', 'approval_chain' => ['manager', 'it']],
        ['code' => 'software', 'name' => 'Software Request', 'approval_chain' => ['manager', 'it']],
        ['code' => 'network', 'name' => 'Network Issue'],
        ['code' => 'access', 'name' => 'Account Access Request', 'approval_chain' => ['manager', 'it']],
        ['code' => 'hr', 'name' => 'HR Request'],
        ['code' => 'finance', 'name' => 'Finance Request', 'approval_chain' => ['manager', 'finance']],
        ['code' => 'procurement', 'name' => 'Procurement Request', 'approval_chain' => ['manager', 'finance']],
        ['code' => 'maintenance', 'name' => 'Maintenance Request'],
        ['code' => 'operations', 'name' => 'Operations Issue'],
        ['code' => 'inquiry', 'name' => 'General Inquiry'],
        ['code' => 'complaint', 'name' => 'Complaint'],
        ['code' => 'service', 'name' => 'Service Request'],
        ['code' => 'incident', 'name' => 'Incident'],
        ['code' => 'change', 'name' => 'Change Request', 'approval_chain' => ['manager', 'it']],
    ];

    public const CATEGORIES = [
        ['code' => 'hardware', 'name' => 'Hardware'],
        ['code' => 'software', 'name' => 'Software'],
        ['code' => 'network', 'name' => 'Network & Connectivity'],
        ['code' => 'accounts', 'name' => 'Accounts & Access'],
        ['code' => 'facilities', 'name' => 'Facilities'],
        ['code' => 'hr_admin', 'name' => 'HR Administration'],
        ['code' => 'finance_ops', 'name' => 'Finance Operations'],
    ];

    public const SERVICES = [
        ['code' => 'new_employee_it', 'name' => 'New Employee IT Setup', 'requires_approval' => true, 'approval_chain' => ['manager', 'it'], 'expected_hours' => 24],
        ['code' => 'email_account', 'name' => 'Email Account Request', 'requires_approval' => true, 'approval_chain' => ['manager', 'it'], 'expected_hours' => 8],
        ['code' => 'laptop', 'name' => 'Laptop Request', 'requires_approval' => true, 'approval_chain' => ['manager', 'it', 'finance'], 'requires_attachment' => true, 'expected_hours' => 72],
        ['code' => 'software_install', 'name' => 'Software Installation', 'requires_approval' => true, 'approval_chain' => ['manager', 'it'], 'expected_hours' => 8],
        ['code' => 'system_access', 'name' => 'System Access Request', 'requires_approval' => true, 'approval_chain' => ['manager', 'it'], 'expected_hours' => 12],
        ['code' => 'internet_access', 'name' => 'Internet Access Request', 'expected_hours' => 4],
        ['code' => 'office_maintenance', 'name' => 'Office Maintenance', 'expected_hours' => 48],
        ['code' => 'procurement_req', 'name' => 'Procurement Request', 'requires_approval' => true, 'approval_chain' => ['manager', 'finance'], 'expected_hours' => 96],
        ['code' => 'hr_docs', 'name' => 'HR Documentation Request', 'expected_hours' => 24],
        ['code' => 'finance_support', 'name' => 'Finance Support Request', 'expected_hours' => 24],
    ];

    public const SLA_POLICIES = [
        ['name' => 'Critical', 'priority' => 'critical', 'response_minutes' => 15, 'resolution_minutes' => 240],
        ['name' => 'Urgent', 'priority' => 'urgent', 'response_minutes' => 30, 'resolution_minutes' => 480],
        ['name' => 'High', 'priority' => 'high', 'response_minutes' => 60, 'resolution_minutes' => 960],
        ['name' => 'Normal', 'priority' => 'normal', 'response_minutes' => 240, 'resolution_minutes' => 2880],
        ['name' => 'Low', 'priority' => 'low', 'response_minutes' => 480, 'resolution_minutes' => 5760],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::PERMISSIONS as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach (self::ROLES as $roleName => $permissions) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])->givePermissionTo($permissions);
            }
            Role::where('name', 'super-admin')->first()?->givePermissionTo(self::PERMISSIONS);

            foreach (self::TYPES as $type) {
                TicketType::firstOrCreate(['code' => $type['code']], $type);
            }
            foreach (self::CATEGORIES as $cat) {
                TicketCategory::firstOrCreate(['code' => $cat['code']], $cat);
            }
            foreach (self::SERVICES as $svc) {
                ServiceCatalogItem::firstOrCreate(['code' => $svc['code']], $svc);
            }
            foreach (self::SLA_POLICIES as $policy) {
                TicketSlaPolicy::firstOrCreate(
                    ['name' => $policy['name'], 'priority' => $policy['priority']], $policy);
            }
        });
    }
}
