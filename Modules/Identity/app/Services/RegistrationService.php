<?php

namespace Modules\Identity\Services;

use App\Services\AuditService;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Enums\ApplicationStatus;
use Modules\Identity\Models\RegistrationApplication;
use Modules\Identity\Models\RegistrationDocument;
use Modules\Identity\Models\RegistrationStatusHistory;

class RegistrationService
{
    /** Statuses that still require IT action. */
    public const OPEN_STATUSES = [
        'submitted', 'pending_review', 'under_review',
        'information_requested', 'approved', 'provisioning',
    ];

    public function __construct(
        private AuditService $audit,
        private FileService $files,
    ) {}

    /* --------------------------- Submission --------------------------- */

    /**
     * Create a registration application. Does NOT create a user or any access.
     */
    public function submit(array $data, array $documents = []): RegistrationApplication
    {
        return DB::transaction(function () use ($data, $documents) {
            $this->assertNotDuplicate($data['email']);

            $application = RegistrationApplication::create([
                ...$data,
                'reference' => $this->nextReference(),
                'status' => ApplicationStatus::Submitted,
                'submitted_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
            ]);

            $this->recordTransition($application, null, ApplicationStatus::Submitted, null, 'Application submitted');

            foreach ($documents as $type => $file) {
                if ($file instanceof UploadedFile) {
                    $this->attachDocument($application, $file, $type);
                }
            }

            $this->audit->log('registration.submitted', $application, new: [
                'reference' => $application->reference,
                'email' => $application->email,
            ]);

            return $application;
        });
    }

    public function attachDocument(RegistrationApplication $application, UploadedFile $file, string $type = 'attachment'): RegistrationDocument
    {
        $managed = $this->files->store($file, 'registration-documents', visibility: 'private');

        return RegistrationDocument::create([
            'registration_application_id' => $application->id,
            'managed_file_id' => $managed->id,
            'document_type' => $type,
            'label' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Same email may not hold two open applications; also block emails that
     * already belong to provisioned ERP users.
     */
    private function assertNotDuplicate(string $email): void
    {
        $open = RegistrationApplication::where('email', $email)
            ->whereIn('status', self::OPEN_STATUSES)->exists();

        if ($open) {
            throw ValidationException::withMessages([
                'email' => 'An application for this email address is already in progress.',
            ]);
        }

        if (\App\Models\User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email address already has an ERP account.',
            ]);
        }
    }

    /** XERIN-REG-YYYY-000001 — race-safe via row locking. */
    private function nextReference(): string
    {
        $year = now()->year;
        $prefix = "XERIN-REG-{$year}-";

        $last = RegistrationApplication::where('reference', 'like', "{$prefix}%")
            ->lockForUpdate()->max('reference');

        $seq = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    /* ---------------------------- Workflow ---------------------------- */

    public function transition(
        RegistrationApplication $application,
        ApplicationStatus $target,
        ?int $actorId = null,
        ?string $note = null
    ): RegistrationApplication {
        return DB::transaction(function () use ($application, $target, $actorId, $note) {
            $application = RegistrationApplication::lockForUpdate()->findOrFail($application->id);
            $from = $application->status;

            if (! $from->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot move application from {$from->value} to {$target->value}.",
                ]);
            }

            $application->update(['status' => $target, 'reviewed_by' => $actorId ?? $application->reviewed_by]);
            $this->recordTransition($application, $from, $target, $actorId, $note);

            $this->audit->log("registration.{$target->value}", $application,
                old: ['status' => $from->value], new: ['status' => $target->value]);

            return $application;
        });
    }

    public function addInternalNote(RegistrationApplication $application, int $reviewerId, string $note): void
    {
        $application->reviews()->create([
            'reviewer_id' => $reviewerId,
            'action' => 'note',
            'note' => $note,
        ]);
    }

    public function requestInformation(RegistrationApplication $application, int $actorId, string $message): RegistrationApplication
    {
        $application->update(['applicant_message' => $message]);
        $app = $this->transition($application, ApplicationStatus::InformationRequested, $actorId, $message);

        $this->notifyApplicant($application, 'Additional information required',
            "{$application->first_name}, the IT team needs more information to process application {$application->reference}: {$message}");

        return $app;
    }

    public function approve(RegistrationApplication $application, int $actorId): RegistrationApplication
    {
        $app = $this->transition($application, ApplicationStatus::Approved, $actorId);
        $app->update(['decided_at' => now()]);

        $this->notifyApplicant($application, 'Application approved',
            "{$application->first_name}, your application {$application->reference} has been approved. Your account is being provisioned — you will receive an activation invitation shortly.");

        return $app;
    }

    public function reject(RegistrationApplication $application, int $actorId, string $reason): RegistrationApplication
    {
        $app = $this->transition($application, ApplicationStatus::Rejected, $actorId, $reason);
        $app->update(['decision_reason' => $reason, 'decided_at' => now()]);

        $this->notifyApplicant($application, 'Application update',
            "{$application->first_name}, application {$application->reference} was not approved. Reason: {$reason}");

        return $app;
    }

    /** On-demand mail to the applicant — they have no user account. */
    private function notifyApplicant(RegistrationApplication $application, string $subject, string $body): void
    {
        try {
            \Illuminate\Support\Facades\Notification::route('mail', $application->email)
                ->notify(new \Modules\Identity\Notifications\IdentityNotification(
                    $subject, $body,
                    url('/register/track'), 'Track application'
                ));
        } catch (\Throwable) {
            // mail transport may be unavailable in dev — never break the workflow
        }
    }

    private function recordTransition(
        RegistrationApplication $application,
        ?ApplicationStatus $from,
        ApplicationStatus $to,
        ?int $actorId,
        ?string $note
    ): void {
        RegistrationStatusHistory::create([
            'registration_application_id' => $application->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'actor_id' => $actorId,
            'note' => $note,
        ]);
    }

    /* ---------------------------- Tracking ---------------------------- */

    /**
     * Public tracking lookup — requires reference AND matching email to
     * prevent enumeration. Returns only applicant-safe fields.
     */
    public function lookupForApplicant(string $reference, string $email): ?RegistrationApplication
    {
        return RegistrationApplication::where('reference', strtoupper(trim($reference)))
            ->where('email', strtolower(trim($email)))
            ->first();
    }

    public function publicTimeline(RegistrationApplication $application): array
    {
        return $application->statusHistories()
            ->get()
            ->map(fn ($h) => [
                'status' => ApplicationStatus::from($h->to_status)->publicLabel(),
                'at' => $h->created_at,
                // applicant_message only when status is information_requested
                'message' => $h->to_status === 'information_requested' ? $application->applicant_message : null,
            ])
            ->values()
            ->all();
    }

    /* --------------------------- Dashboard ---------------------------- */

    public function dashboardStats(): array
    {
        $counts = RegistrationApplication::selectRaw('status, count(*) as n')
            ->groupBy('status')->pluck('n', 'status')->all();

        return [
            'total' => array_sum($counts),
            'submitted' => $counts['submitted'] ?? 0,
            'pending_review' => ($counts['pending_review'] ?? 0) + ($counts['submitted'] ?? 0),
            'under_review' => $counts['under_review'] ?? 0,
            'information_requested' => $counts['information_requested'] ?? 0,
            'approved' => $counts['approved'] ?? 0,
            'rejected' => $counts['rejected'] ?? 0,
            'provisioning' => $counts['provisioning'] ?? 0,
            'active' => $counts['active'] ?? 0,
        ];
    }
}
