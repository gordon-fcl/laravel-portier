<?php

namespace Portier\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Portier\Models\Permission;

class PermissionRevoked
{
    use Dispatchable;

    public function __construct(
        public readonly Model $user,
        public readonly Permission $permission,
    ) {}
}
