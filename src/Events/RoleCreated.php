<?php

namespace Portier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Portier\Models\Role;

class RoleCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Role $role,
    ) {}
}
