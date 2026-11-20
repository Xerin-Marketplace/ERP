<?php
namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Models\PipelineStage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SalesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $perms = ['commercial.dashboard.view','sales.pipeline.manage','sales.opportunities.create',
            'sales.targets.manage','contracts.manage','contracts.approve','partners.manage','partners.verify',
            'commissions.manage','commissions.approve','commissions.settle','pricing.simulate','pricing.approve',
            'campaigns.manage','revenue.analytics.view'];
        foreach ($perms as $p) Permission::firstOrCreate(['name' => $p]);

        $map = ['Sales Officer' => ['commercial.dashboard.view','sales.pipeline.manage','sales.opportunities.create','pricing.simulate'],
            'Commercial Manager' => $perms,
            'Finance Reviewer' => ['commercial.dashboard.view','commissions.approve','commissions.settle','revenue.analytics.view']];
        foreach ($map as $role => $ps) { $r = Role::firstOrCreate(['name' => $role]); $r->givePermissionTo($ps); }

        $stages = [['New','new',1,5],['Contacted','contacted',2,10],['Qualification','qualification',3,20],
            ['Needs Assessment','needs',4,30],['Quotation Sent','quoted',5,50],['Negotiation','negotiation',6,65],
            ['Contract Pending','contract',7,80],['Won','won',8,100],['Lost','lost',9,0]];
        foreach ($stages as [$n,$c,$pos,$prob])
            PipelineStage::firstOrCreate(['code' => $c], ['name' => $n, 'position' => $pos, 'probability' => $prob, 'is_closed' => in_array($c,['won','lost'])]);
    }
}
