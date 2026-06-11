<?php

use Illuminate\Support\Facades\Cache;
use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Services\PermissionRegistrar;
use Portier\Tests\Fixtures\User;

beforeEach(function () {
    $this->registrar = app(PermissionRegistrar::class);
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
});

it('caches permissions on first load', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);

    $permissions = $this->registrar->getPermissions();

    expect($permissions)->toHaveCount(2);
    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_PERMISSIONS))->toBeTrue();
});

it('caches roles with permissions on first load', function () {
    $role = Role::create(['name' => 'editor']);
    $perm = Permission::create(['name' => 'posts.create']);
    $role->permissions()->attach($perm, ['granted' => true]);

    $roles = $this->registrar->getRolesWithPermissions();

    expect($roles)->toHaveCount(1);
    expect($roles->first()->permissions)->toHaveCount(1);
    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_ROLES))->toBeTrue();
});

it('returns cached permissions on subsequent calls without hitting the database', function () {
    Permission::create(['name' => 'posts.create']);

    // First call populates cache
    $this->registrar->getPermissions();

    // Delete from DB — second call should still return cached data
    Permission::query()->delete();

    $permissions = $this->registrar->getPermissions();

    expect($permissions)->toHaveCount(1);
    expect($permissions->first()->name)->toBe('posts.create');
});

it('invalidates permission cache on forgetCachedPermissions', function () {
    Permission::create(['name' => 'posts.create']);
    $this->registrar->getPermissions();

    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_PERMISSIONS))->toBeTrue();

    $this->registrar->forgetCachedPermissions();

    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_PERMISSIONS))->toBeFalse();
    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_ROLES))->toBeFalse();
});

it('warms cache with warmCache method', function () {
    Permission::create(['name' => 'posts.create']);
    Role::create(['name' => 'editor']);

    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_PERMISSIONS))->toBeFalse();

    $this->registrar->warmCache();

    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_PERMISSIONS))->toBeTrue();
    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_ROLES))->toBeTrue();
});

it('uses cached permission for resolvePermissionId in trait', function () {
    Permission::create(['name' => 'posts.create']);

    // Warm cache so resolution uses it
    $this->registrar->getPermissions();

    // Grant should succeed using cached lookup
    $this->user->grantPermission('posts.create');

    expect($this->user->hasPermission('posts.create'))->toBeTrue();
});

it('invalidates user relation cache after grantPermission', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);

    $this->user->grantPermission('posts.create');
    expect($this->user->hasPermission('posts.create'))->toBeTrue();

    $this->user->grantPermission('posts.update');
    expect($this->user->hasPermission('posts.update'))->toBeTrue();
});

it('invalidates user relation cache after revokePermission', function () {
    Permission::create(['name' => 'posts.create']);

    $this->user->grantPermission('posts.create');
    expect($this->user->hasPermission('posts.create'))->toBeTrue();

    $this->user->revokePermission('posts.create');
    expect($this->user->hasPermission('posts.create'))->toBeFalse();
});

it('invalidates user relation cache after syncPermissions', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);

    $this->user->grantPermission('posts.create');
    $this->user->syncPermissions(['posts.update']);

    expect($this->user->hasPermission('posts.create'))->toBeFalse();
    expect($this->user->hasPermission('posts.update'))->toBeTrue();
});

it('invalidates user relation cache after assignRole', function () {
    $role = Role::create(['name' => 'editor']);
    $perm = Permission::create(['name' => 'posts.create']);
    $role->permissions()->attach($perm, ['granted' => true]);

    $this->user->assignRole('editor');

    expect($this->user->hasPermission('posts.create'))->toBeTrue();
});

it('invalidates user relation cache after removeRole', function () {
    $role = Role::create(['name' => 'editor']);
    $perm = Permission::create(['name' => 'posts.create']);
    $role->permissions()->attach($perm, ['granted' => true]);

    $this->user->assignRole('editor');
    expect($this->user->hasPermission('posts.create'))->toBeTrue();

    $this->user->removeRole('editor');
    expect($this->user->hasPermission('posts.create'))->toBeFalse();
});

it('invalidates user relation cache after syncRoles', function () {
    $editor = Role::create(['name' => 'editor']);
    $viewer = Role::create(['name' => 'viewer']);
    $editPerm = Permission::create(['name' => 'posts.create']);
    $viewPerm = Permission::create(['name' => 'posts.read']);
    $editor->permissions()->attach($editPerm, ['granted' => true]);
    $viewer->permissions()->attach($viewPerm, ['granted' => true]);

    $this->user->assignRole('editor');
    $this->user->syncRoles(['viewer']);

    expect($this->user->hasPermission('posts.create'))->toBeFalse();
    expect($this->user->hasPermission('posts.read'))->toBeTrue();
});

it('respects cache.enabled config', function () {
    config(['portier.cache.enabled' => true]);
    expect($this->registrar->isEnabled())->toBeTrue();

    config(['portier.cache.enabled' => false]);
    expect($this->registrar->isEnabled())->toBeFalse();
});

it('portier:cache command warms the cache', function () {
    Permission::create(['name' => 'posts.create']);

    $this->artisan('portier:cache')
        ->expectsOutput('Portier permission cache warmed successfully.')
        ->assertSuccessful();

    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_PERMISSIONS))->toBeTrue();
});

it('portier:clear-cache command clears the cache', function () {
    Permission::create(['name' => 'posts.create']);
    $this->registrar->warmCache();

    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_PERMISSIONS))->toBeTrue();

    $this->artisan('portier:clear-cache')
        ->expectsOutput('Portier permission cache cleared successfully.')
        ->assertSuccessful();

    expect(Cache::store()->has(PermissionRegistrar::CACHE_KEY_PERMISSIONS))->toBeFalse();
});
