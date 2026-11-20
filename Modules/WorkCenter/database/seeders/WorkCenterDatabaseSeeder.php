<?php
namespace Modules\WorkCenter\Database\Seeders;
use Illuminate\Database\Seeder;
use Modules\WorkCenter\Models\{ReportCategory, DocumentCategory, ReportTemplate};
use Spatie\Permission\Models\{Permission, Role};

class WorkCenterDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'work.reports.create','work.reports.review','work.reports.publish',
        'work.documents.upload','work.documents.share','work.documents.manage',
        'work.tasks.assign','work.tasks.review','work.tasks.manage',
        'work.analytics.view','work.templates.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        Role::where('name','super-admin')->first()?->givePermissionTo(self::PERMISSIONS);
        Role::where('name','branch-admin')->first()?->givePermissionTo([
            'work.reports.create','work.reports.review','work.documents.upload',
            'work.documents.share','work.tasks.assign','work.tasks.review','work.analytics.view']);
        Role::firstOrCreate(['name'=>'department-manager','guard_name'=>'web'])
            ->givePermissionTo(['work.reports.create','work.reports.review',
                'work.documents.upload','work.tasks.assign','work.tasks.review']);

        foreach ([['General','GEN'],['Operations','OPS'],['Finance','FIN'],
                  ['HR','HR'],['Tender','TND'],['Executive','EXE']] as [$n,$c]) {
            ReportCategory::firstOrCreate(['code'=>$c],['name'=>$n]);
        }
        foreach ([['Corporate','corporate'],['Legal','legal'],['HR Records','hr'],
                  ['Financial','financial'],['Tender','tender'],['Operational','operational'],
                  ['Contracts','contracts'],['Policies','policies']] as [$n,$c]) {
            DocumentCategory::firstOrCreate(['code'=>$c],['name'=>$n]);
        }
        ReportTemplate::firstOrCreate(
            ['name'=>'Monthly Department Report','category_id'=>ReportCategory::where('code','GEN')->value('id')],
            ['sections'=>[
                ['key'=>'executive_summary','title'=>'Executive Summary','required'=>true],
                ['key'=>'activities','title'=>'Activities Performed','required'=>true],
                ['key'=>'progress','title'=>'Progress Achieved','required'=>true],
                ['key'=>'challenges','title'=>'Challenges','required'=>false],
                ['key'=>'financial','title'=>'Financial Summary','required'=>false],
                ['key'=>'recommendations','title'=>'Recommendations','required'=>false],
                ['key'=>'next_steps','title'=>'Next Steps','required'=>true],
            ],'theme'=>'corporate','default_visibility'=>'department']);
    }
}
