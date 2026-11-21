<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\HR\Models\InterviewRecord;
use Modules\HR\Models\JobVacancy;
use Modules\HR\Models\RecruitmentApplication;
use Modules\HR\Models\RecruitmentRequisition;
use Modules\HR\Notifications\HRNotification;

class RecruitmentService
{
    public const STAGES = ['applied', 'screening', 'shortlisted', 'interview', 'selected', 'offer', 'hired', 'rejected', 'withdrawn'];

    public function __construct(
        private AuditService $audit,
        private HRNumberService $numbers,
    ) {}

    public function requisition(array $data, User $requester): RecruitmentRequisition
    {
        return DB::transaction(function () use ($data, $requester) {
            $r = new RecruitmentRequisition($data);
            $r->requested_by = $requester->id;
            $r->reference = $this->numbers->for('requisition');
            $r->save();
            $r->approvals()->create(['step' => 1, 'approver_role' => 'CTO and Admin']);
            $this->audit->log('recruitment.requisition_created', $r, new: ['title' => $r->title]);
            return $r->fresh();
        });
    }

    public function decideRequisition(RecruitmentRequisition $r, User $decider, string $decision, ?string $note): RecruitmentRequisition
    {
        abort_unless($decider->can('recruitment.approve') || $decider->can('recruitment.manage'), 403);
        if ($r->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Already decided.']);
        }

        $step = $r->approvals()->where('status', 'pending')->firstOrFail();
        return DB::transaction(function () use ($r, $decider, $decision, $note, $step) {
            $step->update(['status' => $decision, 'approver_id' => $decider->id, 'note' => $note, 'decided_at' => now()]);
            $r->update(['status' => $decision]);
            $this->audit->log("recruitment.requisition_{$decision}", $r);
            return $r->fresh();
        });
    }

    public function publishVacancy(array $data, ?RecruitmentRequisition $req = null): JobVacancy
    {
        return DB::transaction(function () use ($data, $req) {
            $v = new JobVacancy($data);
            $v->requisition_id = $req?->id;
            $v->reference = $this->numbers->for('vacancy');
            $v->status = 'published';
            $v->published_at = now();
            $v->save();
            $this->audit->log('recruitment.vacancy_published', $v, new: ['title' => $v->title]);
            return $v;
        });
    }

    /** Public-facing application intake (no auth required — rate-limited at route). */
    public function apply(JobVacancy $vacancy, array $data): RecruitmentApplication
    {
        abort_if($vacancy->status !== 'published', 422, 'This vacancy is not open.');
        abort_if($vacancy->closes_at && $vacancy->closes_at->isPast(), 422, 'This vacancy has closed.');

        return DB::transaction(function () use ($vacancy, $data) {
            $a = new RecruitmentApplication($data);
            $a->vacancy_id = $vacancy->id;
            $a->reference = $this->numbers->for('application');
            $a->save();
            $this->audit->log('recruitment.application_received', $a);
            return $a;
        });
    }

    public function moveStage(RecruitmentApplication $a, string $stage, User $actor, ?string $note = null): RecruitmentApplication
    {
        abort_unless($actor->can('recruitment.manage'), 403);
        abort_unless(in_array($stage, self::STAGES, true), 422, 'Invalid stage.');

        $a->update(['stage' => $stage, 'stage_note' => $note]);
        $this->audit->log('recruitment.stage_changed', $a, new: ['stage' => $stage]);
        return $a->fresh();
    }

    public function scheduleInterview(RecruitmentApplication $a, array $data, User $actor): InterviewRecord
    {
        abort_unless($actor->can('recruitment.manage'), 403);
        abort_unless(in_array($a->stage, ['shortlisted', 'interview'], true), 422, 'Candidate must be shortlisted first.');

        $iv = $a->interviews()->create($data + ['interviewer_id' => $data['interviewer_id'] ?? $actor->id]);
        if ($a->stage === 'shortlisted') $a->update(['stage' => 'interview']);
        $this->audit->log('recruitment.interview_scheduled', $iv);
        return $iv;
    }

    public function recordAssessment(InterviewRecord $iv, int $score, string $assessment, string $outcome, User $actor): InterviewRecord
    {
        abort_unless($actor->can('recruitment.manage'), 403);
        abort_if($score < 1 || $score > 10, 422, 'Score must be 1–10.');
        abort_unless(in_array($outcome, ['pass', 'fail'], true), 422, 'Invalid outcome.');

        $iv->update(['score' => $score, 'assessment' => $assessment, 'outcome' => $outcome]);
        $this->audit->log('recruitment.interview_assessed', $iv, new: ['score' => $score, 'outcome' => $outcome]);
        return $iv->fresh();
    }
}
