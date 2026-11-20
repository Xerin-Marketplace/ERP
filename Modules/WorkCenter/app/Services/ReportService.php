<?php
namespace Modules\WorkCenter\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\WorkCenter\Models\{Report, ReportVersion, ReportReview};

/**
 * Report lifecycle: draft → submitted → in_review → approved|returned → published.
 * Never overwrites content — every save creates a new version.
 */
class ReportService
{
    public function __construct(private AuditService $audit,
        private ReferenceService $refs,
        private \App\Services\FileService $files) {}

    public function create(array $d, User $owner): Report
    {
        $report = Report::create($d + [
            'ref' => $this->refs->next('XER-REP', 'reports'),
            'owner_id' => $owner->id,
            'company_id' => $owner->company_id ?? \Modules\Organization\Models\Company::value('id'),
            'branch_id' => $d['branch_id'] ?? $owner->branch_id,
            'status' => 'draft',
        ]);
        $this->audit->log('report.created', $report);
        return $report;
    }

    /** Builder save — new version, never overwrite. */
    public function saveContent(Report $r, array $content, User $actor, ?string $note = null): ReportVersion
    {
        $this->mustBeEditable($r);
        $v = (int) $r->versions()->max('version') + 1;
        $r->update(['version_no' => $v]);
        $rv = ReportVersion::create(['report_id' => $r->id, 'version' => $v,
            'content' => $content, 'created_by' => $actor->id, 'change_note' => $note]);
        $this->audit->log('report.version_saved', $rv);
        return $rv;
    }

    /** Manual upload — file becomes a version. */
    public function saveUpload(Report $r, UploadedFile $file, User $actor): ReportVersion
    {
        $this->mustBeEditable($r);
        $mf = $this->files->store($file, 'reports');
        $v = (int) $r->versions()->max('version') + 1;
        $r->update(['version_no' => $v]);
        return ReportVersion::create(['report_id' => $r->id, 'version' => $v,
            'file_id' => $mf->id, 'created_by' => $actor->id]);
    }

    public function submit(Report $r, User $actor): Report
    {
        $this->mustBeEditable($r);
        abort_unless($r->versions()->exists(), 422, 'Cannot submit an empty report.');
        $r->update(['status' => 'in_review', 'submitted_at' => now()]);
        $this->audit->log('report.submitted', $r);
        return $r;
    }

    public function review(Report $r, string $decision, User $reviewer, ?string $comment = null): Report
    {
        abort_unless($r->status === 'in_review', 422, 'Report is not awaiting review.');
        abort_if($r->owner_id === $reviewer->id, 422, 'Owner cannot review their own report.');
        abort_unless(in_array($decision, ['approved','rejected','returned']), 422);
        ReportReview::create(['report_id' => $r->id, 'reviewer_id' => $reviewer->id,
            'decision' => $decision, 'comment' => $comment, 'decided_at' => now()]);
        $r->update(['status' => match ($decision) {
            'approved' => 'approved', 'rejected' => 'rejected', default => 'returned',
        }]);
        $this->audit->log("report.{$decision}", $r);
        return $r;
    }

    public function publish(Report $r, User $actor): Report
    {
        abort_unless($r->status === 'approved', 422, 'Only approved reports can be published.');
        $r->update(['status' => 'published', 'published_at' => now()]);
        $this->audit->log('report.published', $r);
        return $r;
    }

    /** Visibility — enforced server-side. */
    public function canView(Report $r, User $u): bool
    {
        if ($u->hasRole('super-admin') || $r->owner_id === $u->id) return true;
        if ($r->reviews()->where('reviewer_id', $u->id)->exists()) return true;
        return match ($r->visibility) {
            'company' => $r->company_id === $u->company_id,
            'branch' => $r->branch_id === $u->branch_id,
            'department' => $r->department_id === $u->department_id,
            'reviewers' => false,
            default => false,
        };
    }

    private function mustBeEditable(Report $r): void
    {
        if (in_array($r->status, ['in_review','approved','published'], true)) {
            throw ValidationException::withMessages(['status' => 'Report is locked — create a revision via resubmit flow.']);
        }
    }
}
