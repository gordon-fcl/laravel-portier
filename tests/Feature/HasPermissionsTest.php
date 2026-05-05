<?php

use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
});

it('can grant a direct permission', function () {
    Permission::create(['name' => 'posts.create']);

    $this->user->grantPermission('posts.create');

    expect($this->user->permissions)->toHaveCount(1)
        ->and($this->user->permissions->first()->name)->toBe('posts.create');
});

it('can grant multiple permissions at once', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);

    $this->user->grantPermission('posts.create', 'posts.update');

    expect($this->user->permissions)->toHaveCount(2);
});

it('can revoke a direct permission', function () {
    Permission::create(['name' => 'posts.create']);

    $this->user->grantPermission('posts.create');
    $this->user->revokePermission('posts.create');
    $this->user->load('permissions');

    expect($this->user->permissions)->toHaveCount(0);
});

it('can sync permissions', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);
    Permission::create(['name' => 'posts.delete']);

    $this->user->grantPermission('posts.create', 'posts.update');
    $this->user->syncPermissions(['posts.update', 'posts.delete']);
    $this->user->load('permissions');

    expect($this->user->permissions)->toHaveCount(2)
        ->and($this->user->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['posts.delete', 'posts.update']);
});

it('checks direct permission via hasPermission', function () {
    Permission::create(['name' => 'posts.create']);

    $this->user->grantPermission('posts.create');

    expect($this->user->hasPermission('posts.create'))->toBeTrue()
        ->and($this->user->hasPermission('posts.delete'))->toBeFalse();
});

it('checks permission via role', function () {
    $role = Role::create(['name' => 'editor']);
    $perm = Permission::create(['name' => 'posts.create']);
    $role->permissions()->attach($perm, ['granted' => true]);

    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    expect($this->user->hasPermission('posts.create'))->toBeTrue();
});

it('checks hasAnyPermission', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.delete']);

    $this->user->grantPermission('posts.create');

    expect($this->user->hasAnyPermission(['posts.create', 'posts.delete']))->toBeTrue()
        ->and($this->user->hasAnyPermission(['posts.delete', 'posts.update']))->toBeFalse();
});

it('checks hasAllPermissions', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);

    $this->user->grantPermission('posts.create', 'posts.update');

    expect($this->user->hasAllPermissions(['posts.create', 'posts.update']))->toBeTrue()
        ->and($this->user->hasAllPermissions(['posts.create', 'posts.delete']))->toBeFalse();
});

it('throws when granting a non-existent permission', function () {
    $this->user->grantPermission('non-existent');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
