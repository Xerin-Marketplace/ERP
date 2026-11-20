<?php

namespace Modules\Reception\Services;

use Illuminate\Validation\ValidationException;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Models\AccessVerification;

class VerificationService
{
    public const TYPES = [
        'email', 'phone', 'employee_lookup', 'manager_confirm',
        'sponsor_confirm', 'document_review',
    ];
    public const STATUSES = ['not_started','pending','verified','failed','info_required','not_applicable'];

    /** Seed the checks appropriate for the request type. */
    public function bootstrap(AccessRequest $request): void
    {
        $types = match ($request->type) {
            'new_employee' => ['email', 'employee_lookup', 'document_review'],
            'existing_employee' => ['employee_lookup', 'manager_confirm'],
            'external' => ['email', 'sponsor_confirm', 'document_review'],
            default => ['email'],
        };
        foreach ($types as $type) {
            AccessVerification::firstOrCreate(
                ['request_id' => $request->id, 'type' => $type],
                ['status' => 'not_started']
            );
        }
    }

    public function record(AccessVerification $check, int $reviewerId, string $status,
        ?string $notes = null, ?string $evidence = null): AccessVerification
    {
        abort_unless(in_array($status, self::STATUSES, true), 422);
        $check->update([
            'status' => $status, 'reviewer_id' => $reviewerId,
            'notes' => $notes, 'evidence' => $evidence,
            'verified_at' => $status === 'verified' ? now() : null,
        ]);
        return $check;
    }

    /** All required checks verified (or n/a) — gate for routing to approvals. */
    public function isComplete(AccessRequest $request): bool
    {
        return ! $request->verifications()
            ->whereNotIn('status', ['verified', 'not_applicable'])->exists();
    }
}
