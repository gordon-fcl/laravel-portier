<?php

namespace Portier\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Portier\Models\Role;
use Portier\Services\PermissionRegistrar;

trait HasRoles
{
    public function roles(): MorphToMany
    {
        $prefix = config('portier.table_prefix', 'portier_');

        return $this->morphToMany(Role::class, 'user', $prefix.'user_roles', null, 'role_id');
    }

    public function assignRole(string|Role ...$roles): void
    {
        $ids = collect($roles)->map(fn ($role) => $this->resolveRoleId($role))->all();
        $this->roles()->syncWithoutDetaching($ids);
        $this->invalidateRoleCache();
    }

    public function removeRole(string|Role ...$roles): void
    {
        $ids = collect($roles)->map(fn ($role) => $this->resolveRoleId($role))->all();
        $this->roles()->detach($ids);
        $this->invalidateRoleCache();
    }

    public function syncRoles(array $roles): void
    {
        $ids = collect($roles)->map(fn ($role) => $this->resolveRoleId($role))->all();
        $this->roles()->sync($ids);
        $this->invalidateRoleCache();
    }

    public function hasRole(string|Role $role): bool
    {
        $name = $role instanceof Role ? $role->name : $role;

        return $this->roles->contains('name', $name);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function resolveRoleId(string|Role $role): int
    {
        if ($role instanceof Role) {
            return $role->id;
        }

        return Role::where('name', $role)->firstOrFail()->id;
    }

    private function invalidateRoleCache(): void
    {
        $this->unsetRelation('roles');

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetUserCache($this->getKey(), $this->getMorphClass());
    }
}
