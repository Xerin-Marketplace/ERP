<?php

namespace Modules\Organization\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class OrganizationalEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $entity,
        public array $changes = [],
    ) {}
}
