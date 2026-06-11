<?php

namespace Portier\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Portier\Models\Permission;
use Portier\Models\Role;

class PermissionRegistrar
{
    public const CACHE_KEY_PERMISSIONS = 'portier.permissions';

    public const CACHE_KEY_ROLES = 'portier.roles';

    public const CACHE_KEY_USER_PREFIX = 'portier.user.';

    private ?Collection $permissions = null;

    private ?Collection $roles = null;

    public function getPermissions(): Collection
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }

        $this->permissions = $this->getCache()->remember(
            static::CACHE_KEY_PERMISSIONS,
            $this->getTtl(),
            fn () => Permission::all()
        );

        return $this->permissions;
    }

    public function getRolesWithPermissions(): Collection
    {
        if ($this->roles !== null) {
            return $this->roles;
        }

        $this->roles = $this->getCache()->remember(
            static::CACHE_KEY_ROLES,
            $this->getTtl(),
            fn () => Role::with('permissions', 'parent.permissions')->get()
        );

        return $this->roles;
    }

    public function forgetCachedPermissions(): void
    {
        $this->permissions = null;
        $this->roles = null;
        $this->getCache()->forget(static::CACHE_KEY_PERMISSIONS);
        $this->getCache()->forget(static::CACHE_KEY_ROLES);
    }

    public function forgetUserCache(int $userId, string $userType): void
    {
        $this->getCache()->forget(static::CACHE_KEY_USER_PREFIX.$userType.'.'.$userId);
    }

    public function clearAllUserCaches(): void
    {
        // User caches use a tagged approach if the store supports it,
        // otherwise we rely on per-user invalidation on write operations.
        $this->forgetCachedPermissions();
    }

    public function warmCache(): void
    {
        $this->forgetCachedPermissions();
        $this->getPermissions();
        $this->getRolesWithPermissions();
    }

    public function isEnabled(): bool
    {
        return config('portier.cache.enabled', true);
    }

    public function getCache(): Repository
    {
        $store = config('portier.cache.store');

        return Cache::store($store);
    }

    private function getTtl(): int
    {
        return config('portier.cache.ttl', 3600);
    }
}
