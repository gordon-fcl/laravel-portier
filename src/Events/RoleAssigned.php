<?php

namespace Portier\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Portier\Models\Role;

class RoleAssigned
{
    use Dispatchable;

    public function __construct(
        public readonly Model $user,
        public readonly Role $role,
    ) {}
}
