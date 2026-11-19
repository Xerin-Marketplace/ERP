<?php

namespace Modules\Identity\Enums;

enum AssignmentStatus: string
{
    case Active = 'active';
    case Temporary = 'temporary';
    case Ended = 'ended';
}
