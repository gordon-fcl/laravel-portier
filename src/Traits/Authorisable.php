<?php

namespace Portier\Traits;

trait Authorisable
{
    use HasRoles, HasPermissions;

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('portier.super_admin_role', 'super-admin'));
    }
}
