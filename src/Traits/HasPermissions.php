<?php

namespace Portier\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Portier\Events\PermissionGranted;
use Portier\Events\PermissionRevoked;
use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Services\PermissionRegistrar;

trait HasPermissions
{
    public function permissions(): MorphToMany
    {
        $prefix = config('portier.table_prefix', 'portier_');

        return $this->morphToMany(Permission::class, 'user', $prefix.'user_permissions', null, 'permission_id')
            ->withPivot('granted');
    }

    public function grantPermission(string|Permission ...$permissions): void
    {
        $resolved = collect($permissions)->map(fn ($p) => $this->resolvePermission($p));
        $sync = $resolved->mapWithKeys(fn (Permission $p) => [$p->id => ['granted' => true]])->all();
        $this->permissions()->syncWithoutDetaching($sync);
        $this->invalidatePermissionCache();

        $resolved->each(fn (Permission $p) => PermissionGranted::dispatch($this, $p));
    }

    public function revokePermission(string|Permission ...$permissions): void
    {
        $resolved = collect($permissions)->map(fn ($p) => $this->resolvePermission($p));
        $this->permissions()->detach($resolved->pluck('id')->all());
        $this->invalidatePermissionCache();

        $resolved->each(fn (Permission $p) => PermissionRevoked::dispatch($this, $p));
    }

    public function syncPermissions(array $permissions): void
    {
        $sync = collect($permissions)->mapWithKeys(fn ($p) => [
            $this->resolvePermissionId($p) => ['granted' => true],
        ])->all();

        $this->permissions()->sync($sync);
        $this->invalidatePermissionCache();
    }

    public function hasPermission(string $permission): bool
    {
        // Check direct user permissions first
        $direct = $this->getDirectPermissionGrant($permission);
        if ($direct !== null) {
            if (config('portier.direct_overrides_role', true)) {
                return $direct;
            }
        }

        // Check role-based permissions (including inheritance and wildcards)
        $roleGrant = $this->getRolePermissionGrant($permission);

        // If direct doesn't override, direct denial still wins
        if ($direct !== null && ! config('portier.direct_overrides_role', true)) {
            return $direct && ($roleGrant !== false);
        }

        if ($roleGrant !== null) {
            return $roleGrant;
        }

        return $direct ?? false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    private function getDirectPermissionGrant(string $permission): ?bool
    {
        $directPermissions = $this->permissions;

        // Exact match
        $exact = $directPermissions->firstWhere('name', $permission);
        if ($exact) {
            return (bool) $exact->pivot->granted;
        }

        // Wildcard match (e.g. user has direct "posts.*")
        foreach ($directPermissions as $directPerm) {
            if ($this->wildcardMatches($directPerm->name, $permission)) {
                return (bool) $directPerm->pivot->granted;
            }
        }

        return null;
    }

    private function getRolePermissionGrant(string $permission): ?bool
    {
        $roles = $this->roles;
        $allRolePermissions = collect();

        foreach ($roles as $role) {
            $allRolePermissions = $allRolePermissions->merge($this->collectRolePermissions($role));
        }

        // Check for explicit denial first
        $denied = $allRolePermissions->first(function ($rp) use ($permission) {
            return ($rp['name'] === $permission || $this->wildcardMatches($rp['name'], $permission))
                && ! $rp['granted'];
        });

        if ($denied) {
            return false;
        }

        // Check for grant
        $granted = $allRolePermissions->first(function ($rp) use ($permission) {
            return ($rp['name'] === $permission || $this->wildcardMatches($rp['name'], $permission))
                && $rp['granted'];
        });

        return $granted ? true : null;
    }

    private function collectRolePermissions(Role $role): array
    {
        $permissions = $role->permissions->map(fn ($p) => [
            'name' => $p->name,
            'granted' => (bool) $p->pivot->granted,
        ])->all();

        // Walk inheritance chain
        if (config('portier.role_inheritance', true) && $role->parent_id) {
            $role->loadMissing('parent.permissions');
            if ($role->parent) {
                $permissions = array_merge($permissions, $this->collectRolePermissions($role->parent));
            }
        }

        return $permissions;
    }

    private function wildcardMatches(string $pattern, string $permission): bool
    {
        if (! str_contains($pattern, '*')) {
            return false;
        }

        $prefix = rtrim($pattern, '.*');

        return str_starts_with($permission, $prefix.'.');
    }

    private function resolvePermissionId(string|Permission $permission): int
    {
        return $this->resolvePermission($permission)->id;
    }

    private function resolvePermission(string|Permission $permission): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        $registrar = app(PermissionRegistrar::class);

        if ($registrar->isEnabled()) {
            $cached = $registrar->getPermissions()->firstWhere('name', $permission);
            if ($cached) {
                return $cached;
            }
        }

        return Permission::where('name', $permission)->firstOrFail();
    }

    private function invalidatePermissionCache(): void
    {
        $this->unsetRelation('permissions');

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetUserCache($this->getKey(), $this->getMorphClass());
    }
}
