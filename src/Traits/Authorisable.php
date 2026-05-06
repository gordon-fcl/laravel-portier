<?php

namespace Portier\Traits;

trait Authorisable
{
    use HasPermissions, HasRoles;

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('portier.super_admin_role', 'super-admin'));
    }
}
