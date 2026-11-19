<?php

namespace Modules\Organization\Enums;

enum EntityStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
