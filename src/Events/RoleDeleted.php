<?php

namespace Portier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Portier\Models\Role;

class RoleDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly Role $role,
    ) {}
}
