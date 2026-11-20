<?php

namespace Modules\Reception\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Models\AccessRequestHistory;

class AccessRequestService
{
    public const TYPES = ['new_employee', 'existing_employee', 'external'];
    public const STATUSES = [
        'submitted', 'reception_review', 'info_required', 'verification',
        'in_approval', 'approved', 'provisioning', 'provisioned',
        'activated', 'rejected', 'cancelled', 'expired',
    ];

    public function __construct(
        private AuditService $audit,
        private AccessWorkflowService $workflows,
    ) {}

    /** Public submission — generates XER-ACC ref + secure tracking token. */
    public function submit(array $data, ?string $ip = null): AccessRequest
    {
        $this->validateType($data);

        // Duplicate-submission guard: same email + same type still open.
        $open = AccessRequest::where('email', $data['email'])
            ->where('type', $data['type'])
            ->whereNotIn('status', ['rejected', 'cancelled', 'activated', 'provisioned', 'expired'])
            ->exists();
        if ($open) {
            throw ValidationException::withMessages([
                'email' => 'An active request with this email and type already exists. Track it instead.',
            ]);
        }

        return DB::transaction(function () use ($data, $ip) {
            $request = AccessRequest::create([
                'reference'    => $this->nextReference(),
                'type'         => $data['type'],
                'status'       => 'submitted',
                'full_name'    => $data['full_name'],
                'email'        => strtolower($data['email']),
                'phone'        => $data['phone'] ?? null,
                'organization' => $data['organization'] ?? null,
                'company_id'   => $data['company_id'] ?? null,
                'branch_id'    => $data['branch_id'] ?? null,
                'department_id'=> $data['department_id'] ?? null,
                'job_title'    => $data['job_title'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'manager_id'   => $data['manager_id'] ?? null,
                'employee_id'  => $data['employee_id'] ?? null,
                'sponsor_id'   => $data['sponsor_id'] ?? null,
                'start_date'   => $data['start_date'] ?? null,
                'expiry_date'  => $data['expiry_date'] ?? null,
                'requested_modules' => $data['requested_modules'] ?? [],
                'requested_changes' => $data['requested_changes'] ?? null,
                'justification' => $data['justification'] ?? null,
                'tracking_token' => hash('sha256', Str::random(48).$data['email']),
                'priority'     => $data['type'] === 'external' ? 'normal' : ($data['priority'] ?? 'normal'),
                'submitted_at' => now(),
            ]);
            $this->record($request, 'request.submitted', null, 'submitted',
                ['type' => $request->type], false, $ip);
            $this->audit->log('access.request_submitted', $request,
                new: ['type' => $request->type, 'email' => $request->email]);
            return $request;
        });
    }

    /** Secure public tracking — token + email both required. */
    public function findForTracking(string $reference, string $token): ?AccessRequest
    {
        return AccessRequest::where('reference', strtoupper(trim($reference)))
            ->where('tracking_token', $token)
            ->first();
    }

    public function startReview(AccessRequest $request, int $actorId): AccessRequest
    {
        return $this->move($request, 'reception_review', $actorId, 'reception.review_started');
    }

    /** Return to applicant for missing info — public-visible note. */
    public function requestInfo(AccessRequest $request, int $actorId, string $note): AccessRequest
    {
        $request->history()->create([
            'actor_id' => $actorId, 'event' => 'reception.info_requested',
            'payload' => ['note' => $note], 'internal' => false,
        ]);
        return $this->move($request, 'info_required', $actorId, 'status.info_required');
    }

    public function addInternalNote(AccessRequest $request, int $actorId, string $note): void
    {
        $request->history()->create([
            'actor_id' => $actorId, 'event' => 'internal.note',
            'payload' => ['note' => $note], 'internal' => true,
        ]);
    }

    /** Reception routes: pin a workflow version and emit stage-1 tasks. */
    public function route(AccessRequest $request, int $actorId): AccessRequest
    {
        if (! in_array($request->status, ['submitted', 'reception_review', 'verification'], true)) {
            throw ValidationException::withMessages(['request' => "Cannot route a request in status [{$request->status}]."]);
        }
        return DB::transaction(function () use ($request, $actorId) {
            $version = $this->workflows->resolve($request);
            if (! $version || $version->stages->isEmpty()) {
                throw ValidationException::withMessages(['workflow' => 'No active workflow matches this request.']);
            }
            $request->update(['workflow_version_id' => $version->id, 'current_stage_seq' => 0]);
            $request = $this->move($request, 'in_approval', $actorId, 'reception.routed',
                ['workflow_version' => $version->version]);
            $this->workflows->emitStageTasks($request->fresh(), 1);
            return $request->fresh();
        });
    }

    public function reject(AccessRequest $request, int $actorId, string $reason): AccessRequest
    {
        if (in_array($request->status, ['provisioned', 'activated', 'rejected'], true)) {
            throw ValidationException::withMessages(['request' => 'Request can no longer be rejected.']);
        }
        $request->tasks()->where('status', 'pending')->update(['status' => 'skipped', 'decided_at' => now()]);
        $request->update(['decision_reason' => $reason, 'decided_at' => now()]);
        return $this->move($request, 'rejected', $actorId, 'request.rejected', ['reason' => $reason]);
    }

    public function markApproved(AccessRequest $request, int $actorId): AccessRequest
    {
        $request->update(['decided_at' => now()]);
        return $this->move($request, 'approved', $actorId, 'request.approved');
    }

    public function markProvisioning(AccessRequest $request, int $actorId): AccessRequest
    {
        return $this->move($request, 'provisioning', $actorId, 'provisioning.started');
    }

    public function markProvisioned(AccessRequest $request, int $actorId, int $userId): AccessRequest
    {
        $request->update(['provisioned_user_id' => $userId, 'provisioned_at' => now()]);
        return $this->move($request, 'provisioned', $actorId, 'provisioning.completed', ['user_id' => $userId]);
    }

    private function move(AccessRequest $request, string $to, ?int $actorId, string $event, array $payload = []): AccessRequest
    {
        $from = $request->status;
        abort_unless(in_array($to, self::STATUSES, true), 422);
        $request->update(['status' => $to]);
        $this->record($request, $event, $from, $to, $payload, false, null, $actorId);
        $this->audit->log("access.{$event}", $request, old: ['status' => $from], new: ['status' => $to]);
        return $request->fresh();
    }

    public function record(AccessRequest $request, string $event, ?string $from, ?string $to,
        array $payload = [], bool $internal = false, ?string $ip = null, ?int $actorId = null): void
    {
        AccessRequestHistory::create([
            'request_id' => $request->id, 'actor_id' => $actorId, 'event' => $event,
            'from_status' => $from, 'to_status' => $to, 'payload' => $payload,
            'internal' => $internal, 'ip' => $ip,
        ]);
    }

    private function nextReference(): string
    {
        $prefix = 'XER-ACC-'.now()->format('Y').'-';
        $max = AccessRequest::where('reference', 'like', "{$prefix}%")->lockForUpdate()->max('reference');
        return sprintf('%s%06d', $prefix, $max ? ((int) substr($max, -6)) + 1 : 1);
    }

    private function validateType(array $data): void
    {
        abort_unless(in_array($data['type'] ?? '', self::TYPES, true), 422, 'Unknown request type.');
        if ($data['type'] === 'existing_employee' && empty($data['employee_id'])) {
            throw ValidationException::withMessages(['employee_id' => 'Existing-employee requests must reference an employee record.']);
        }
        if ($data['type'] === 'external' && (empty($data['expiry_date']) || empty($data['sponsor_id']))) {
            throw ValidationException::withMessages(['external' => 'External requests require an internal sponsor and an expiry date.']);
        }
    }
}
