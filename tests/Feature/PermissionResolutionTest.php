<?php

use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
});

// --- Role Inheritance ---

it('inherits permissions from parent role', function () {
    $parent = Role::create(['name' => 'manager']);
    $child = Role::create(['name' => 'team-lead', 'parent_id' => $parent->id]);

    $perm = Permission::create(['name' => 'reports.view']);
    $parent->permissions()->attach($perm, ['granted' => true]);

    $this->user->assignRole('team-lead');
    $this->user->load('roles.permissions', 'roles.parent.permissions');

    expect($this->user->hasPermission('reports.view'))->toBeTrue();
});

it('inherits permissions through multiple levels', function () {
    $grandparent = Role::create(['name' => 'director']);
    $parent = Role::create(['name' => 'manager', 'parent_id' => $grandparent->id]);
    $child = Role::create(['name' => 'team-lead', 'parent_id' => $parent->id]);

    $perm = Permission::create(['name' => 'budget.approve']);
    $grandparent->permissions()->attach($perm, ['granted' => true]);

    $this->user->assignRole('team-lead');
    $this->user->load('roles.permissions', 'roles.parent.permissions');

    expect($this->user->hasPermission('budget.approve'))->toBeTrue();
});

it('does not inherit when role_inheritance is disabled', function () {
    config(['portier.role_inheritance' => false]);

    $parent = Role::create(['name' => 'manager']);
    $child = Role::create(['name' => 'team-lead', 'parent_id' => $parent->id]);

    $perm = Permission::create(['name' => 'reports.view']);
    $parent->permissions()->attach($perm, ['granted' => true]);

    $this->user->assignRole('team-lead');
    $this->user->load('roles.permissions');

    expect($this->user->hasPermission('reports.view'))->toBeFalse();
});

// --- Wildcards ---

it('grants permission via wildcard', function () {
    $role = Role::create(['name' => 'editor']);
    $wildcard = Permission::create(['name' => 'posts.*']);
    $role->permissions()->attach($wildcard, ['granted' => true]);

    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    expect($this->user->hasPermission('posts.create'))->toBeTrue()
        ->and($this->user->hasPermission('posts.delete'))->toBeTrue()
        ->and($this->user->hasPermission('users.create'))->toBeFalse();
});

it('grants direct wildcard permission', function () {
    Permission::create(['name' => 'posts.*']);

    $this->user->grantPermission('posts.*');

    expect($this->user->hasPermission('posts.create'))->toBeTrue()
        ->and($this->user->hasPermission('posts.update'))->toBeTrue()
        ->and($this->user->hasPermission('comments.create'))->toBeFalse();
});

// --- Explicit Denials ---

it('denies permission when role has explicit denial', function () {
    $role = Role::create(['name' => 'restricted-editor']);
    $perm = Permission::create(['name' => 'posts.delete']);
    $role->permissions()->attach($perm, ['granted' => false]);

    $this->user->assignRole('restricted-editor');
    $this->user->load('roles.permissions');

    expect($this->user->hasPermission('posts.delete'))->toBeFalse();
});

it('direct grant overrides role denial when direct_overrides_role is true', function () {
    config(['portier.direct_overrides_role' => true]);

    $role = Role::create(['name' => 'restricted']);
    $perm = Permission::create(['name' => 'posts.delete']);
    $role->permissions()->attach($perm, ['granted' => false]);

    $this->user->assignRole('restricted');
    $this->user->grantPermission('posts.delete');
    $this->user->load('roles.permissions', 'permissions');

    expect($this->user->hasPermission('posts.delete'))->toBeTrue();
});

it('direct grant does not override role denial when direct_overrides_role is false', function () {
    config(['portier.direct_overrides_role' => false]);

    $role = Role::create(['name' => 'restricted']);
    $perm = Permission::create(['name' => 'posts.delete']);
    $role->permissions()->attach($perm, ['granted' => false]);

    $this->user->assignRole('restricted');
    $this->user->grantPermission('posts.delete');
    $this->user->load('roles.permissions', 'permissions');

    expect($this->user->hasPermission('posts.delete'))->toBeFalse();
});

// --- Super Admin ---

it('identifies a super admin', function () {
    Role::create(['name' => 'super-admin']);

    $this->user->assignRole('super-admin');

    expect($this->user->isSuperAdmin())->toBeTrue();
});

it('non-super-admin user is not super admin', function () {
    Role::create(['name' => 'editor']);

    $this->user->assignRole('editor');

    expect($this->user->isSuperAdmin())->toBeFalse();
});

it('respects custom super_admin_role config', function () {
    config(['portier.super_admin_role' => 'god-mode']);
    Role::create(['name' => 'god-mode']);

    $this->user->assignRole('god-mode');

    expect($this->user->isSuperAdmin())->toBeTrue();
});

// --- Edge Cases ---

it('returns false for a permission that does not exist in the database', function () {
    expect($this->user->hasPermission('nonexistent.permission'))->toBeFalse();
});

it('handles user with no roles or permissions', function () {
    expect($this->user->hasPermission('anything'))->toBeFalse()
        ->and($this->user->hasRole('anything'))->toBeFalse();
});
