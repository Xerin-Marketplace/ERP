<?php
namespace Modules\WorkCenter\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\{Company, Branch};
use Modules\WorkCenter\Database\Seeders\WorkCenterDatabaseSeeder;
use Modules\WorkCenter\Models\{Report, ReportCategory, Document, DocumentCategory, WorkTask};
use Modules\WorkCenter\Services\{ReportService, DocumentService, TaskService, PdfService};
use Tests\TestCase;

class WorkCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin, $emp, $mgr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(WorkCenterDatabaseSeeder::class);
        $this->admin = User::factory()->create(['status'=>'active']); $this->admin->assignRole('super-admin');
        $this->emp = User::factory()->create(['status'=>'active']); $this->emp->givePermissionTo('work.reports.create');
        $this->mgr = User::factory()->create(['status'=>'active']); $this->mgr->givePermissionTo(['work.reports.review','work.tasks.assign','work.tasks.review']);
    }

    private function report(User $owner): Report
    {
        return app(ReportService::class)->create([
            'title'=>'Monthly Ops','category_id'=>ReportCategory::value('id'),
            'type'=>'builder','visibility'=>'private','classification'=>'internal'], $owner);
    }

    public function test_report_ref_sequence(): void
    {
        $r = $this->report($this->emp);
        $this->assertMatchesRegularExpression('/XER-REP-\d{4}-\d{6}/', $r->ref);
    }

    public function test_builder_versions_never_overwrite(): void
    {
        $r = $this->report($this->emp);
        app(ReportService::class)->saveContent($r, ['summary'=>'v1'], $this->emp);
        app(ReportService::class)->saveContent($r->fresh(), ['summary'=>'v2'], $this->emp);
        $this->assertSame(2, $r->fresh()->version_no);
        $this->assertSame(2, $r->versions()->count());
        $this->assertSame('v1', $r->versions()->where('version',1)->value('content')['summary'] ?? json_decode($r->versions()->where('version',1)->value('content'),true)['summary']);
    }

    public function test_upload_report_creates_file_version(): void
    {
        $r = $this->report($this->emp);
        $file = UploadedFile::fake()->create('report.pdf', 50, 'application/pdf');
        $v = app(ReportService::class)->saveUpload($r, $file, $this->emp);
        $this->assertNotNull($v->file_id);
    }

    public function test_submit_locks_and_owner_cannot_self_review(): void
    {
        $r = $this->report($this->emp);
        $svc = app(ReportService::class);
        $svc->saveContent($r, ['summary'=>'x'], $this->emp);
        $svc->submit($r->fresh(), $this->emp);
        $this->assertSame('in_review', $r->fresh()->status);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $svc->review($r->fresh(), 'approved', $this->emp);
    }

    public function test_review_approves_and_publishes(): void
    {
        $r = $this->report($this->emp);
        $svc = app(ReportService::class);
        $svc->saveContent($r, ['summary'=>'x'], $this->emp);
        $svc->submit($r->fresh(), $this->emp);
        $svc->review($r->fresh(), 'approved', $this->mgr, 'good');
        $this->assertSame('approved', $r->fresh()->status);
        $svc->publish($r->fresh(), $this->admin);
        $this->assertSame('published', $r->fresh()->status);
    }

    public function test_visibility_enforced(): void
    {
        $r = $this->report($this->emp); // private
        $svc = app(ReportService::class);
        $this->assertFalse($svc->canView($r, $this->mgr));
        $this->assertTrue($svc->canView($r, $this->emp));
        $this->assertTrue($svc->canView($r, $this->admin)); // super-admin global
    }

    public function test_pdf_generation_produces_file(): void
    {
        $r = $this->report($this->emp);
        $svc = app(ReportService::class);
        $svc->saveContent($r, ['summary'=>'<p>Test body</p>'], $this->emp);
        $v = $r->fresh()->versions()->latest('version')->first();
        $path = app(PdfService::class)->render($r->fresh(), $v);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists($path));
        $this->assertSame('%PDF', substr(\Illuminate\Support\Facades\Storage::disk('local')->get($path), 0, 4));
    }

    public function test_document_upload_version_share(): void
    {
        $svc = app(DocumentService::class);
        $d = $svc->upload(['title'=>'Contract','category_id'=>DocumentCategory::value('id'),
            'visibility'=>'private'], UploadedFile::fake()->create('c.pdf',30,'application/pdf'), $this->emp);
        $this->assertMatchesRegularExpression('/XER-DOC-\d{4}-\d{6}/', $d->ref);
        $svc->newVersion($d, UploadedFile::fake()->create('c2.pdf',30,'application/pdf'), $this->emp, 'rev');
        $this->assertSame(2, $d->fresh()->version_no);
        $svc->share($d, $this->mgr, $this->emp);
        $this->assertTrue($svc->canView($d, $this->mgr));
        $this->assertFalse($svc->canView($d, User::factory()->create()));
    }

    public function test_document_expiry_days(): void
    {
        $d = app(DocumentService::class)->upload(['title'=>'x','category_id'=>DocumentCategory::value('id'),
            'visibility'=>'private','expires_at'=>now()->addDays(15)],
            UploadedFile::fake()->create('x.pdf',10,'application/pdf'), $this->emp);
        $this->assertSame(15, app(DocumentService::class)->expiryInDays($d));
    }

    public function test_task_full_lifecycle_review_required(): void
    {
        $svc = app(TaskService::class);
        $t = $svc->assign(['title'=>'Ops report','priority'=>'high','assignee_id'=>$this->emp->id,
            'due_at'=>now()->addDays(5)], $this->mgr);
        $this->assertMatchesRegularExpression('/XER-TASK-\d{4}-\d{6}/', $t->ref);
        $svc->move($t, 'accepted', $this->emp);
        $svc->move($t->fresh(), 'in_progress', $this->emp);
        $svc->progress($t->fresh(), 50, $this->emp, 'half done');
        $svc->progress($t->fresh(), 100, $this->emp, 'all done');
        $this->assertSame('in_progress', $t->fresh()->status); // 100% ≠ completed
        $svc->submitForReview($t->fresh(), $this->emp);
        $svc->review($t->fresh(), 'approved', $this->mgr, 'ok');
        $this->assertSame('completed', $t->fresh()->status);
        $this->assertSame(2, $t->updates()->count()); // history preserved
    }

    public function test_revision_returns_to_work(): void
    {
        $svc = app(TaskService::class);
        $t = $svc->assign(['title'=>'x','priority'=>'low','assignee_id'=>$this->emp->id], $this->mgr);
        $svc->move($t,'accepted',$this->emp); $svc->move($t->fresh(),'in_progress',$this->emp);
        $svc->submitForReview($t->fresh(),$this->emp);
        $svc->review($t->fresh(),'revision',$this->mgr,'fix numbers');
        $this->assertSame('revision_required', $t->fresh()->status);
    }

    public function test_assignee_cannot_review_own_task(): void
    {
        $svc = app(TaskService::class);
        $t = $svc->assign(['title'=>'x','priority'=>'low','assignee_id'=>$this->emp->id], $this->mgr);
        $svc->move($t,'accepted',$this->emp); $svc->move($t->fresh(),'in_progress',$this->emp);
        $svc->submitForReview($t->fresh(),$this->emp);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $svc->review($t->fresh(),'approved',$this->emp);
    }

    public function test_overdue_detection(): void
    {
        $svc = app(TaskService::class);
        $svc->assign(['title'=>'late','priority'=>'medium','assignee_id'=>$this->emp->id,
            'due_at'=>now()->subDay()], $this->mgr);
        $this->assertSame(1, $svc->overdue()->count());
    }
}
