<?php

namespace Modules\Identity\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PendingReview = 'pending_review';
    case UnderReview = 'under_review';
    case InformationRequested = 'information_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Cancelled = 'cancelled';

    /** Allowed forward transitions for the IT workflow. */
    public function transitions(): array
    {
        return match ($this) {
            self::Submitted => [self::PendingReview, self::UnderReview, self::Cancelled],
            self::PendingReview => [self::UnderReview, self::Cancelled],
            self::UnderReview => [self::InformationRequested, self::Approved, self::Rejected, self::Cancelled],
            self::InformationRequested => [self::UnderReview, self::Cancelled],
            self::Approved => [self::Provisioning, self::Cancelled],
            self::Provisioning => [self::Active],
            self::Draft, self::Rejected, self::Active, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    /** Statuses visible on the public tracking page (internal notes never shown). */
    public function publicLabel(): string
    {
        return match ($this) {
            self::Submitted, self::PendingReview => 'Pending IT Review',
            self::UnderReview => 'Under Review',
            self::InformationRequested => 'Additional Information Required',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Provisioning => 'Account Provisioning',
            self::Active => 'Activated',
            self::Draft => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }
}
