<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending approval',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Deactivated => 'Deactivated',
        };
    }
}
