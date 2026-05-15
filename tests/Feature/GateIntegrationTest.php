<?php

use Illuminate\Support\Facades\Gate;
use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
});

it('allows access via Gate when user has permission', function () {
    $role = Role::create(['name' => 'editor']);
    Permission::create(['name' => 'posts.create']);
    $role->permissions()->attach(Permission::first(), ['granted' => true]);
    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    expect(Gate::forUser($this->user)->allows('posts.create'))->toBeTrue();
});

it('denies access via Gate when user lacks permission', function () {
    expect(Gate::forUser($this->user)->allows('posts.create'))->toBeFalse();
});

it('super admin passes all Gate checks', function () {
    Role::create(['name' => 'super-admin']);
    $this->user->assignRole('super-admin');
    $this->user->load('roles');

    expect(Gate::forUser($this->user)->allows('anything.at.all'))->toBeTrue();
});

it('returns null to allow policies to run when portier has no opinion', function () {
    Gate::define('custom-check', fn ($user) => true);

    expect(Gate::forUser($this->user)->allows('custom-check'))->toBeTrue();
});

it('user can() method works via Gate hook', function () {
    $role = Role::create(['name' => 'editor']);
    Permission::create(['name' => 'posts.update']);
    $role->permissions()->attach(Permission::first(), ['granted' => true]);
    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    $this->actingAs($this->user);

    expect($this->user->can('posts.update'))->toBeTrue()
        ->and($this->user->can('posts.delete'))->toBeFalse();
});

it('wildcard permissions work through Gate', function () {
    $role = Role::create(['name' => 'editor']);
    Permission::create(['name' => 'posts.*']);
    $role->permissions()->attach(Permission::first(), ['granted' => true]);
    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    expect(Gate::forUser($this->user)->allows('posts.create'))->toBeTrue()
        ->and(Gate::forUser($this->user)->allows('posts.delete'))->toBeTrue()
        ->and(Gate::forUser($this->user)->allows('users.create'))->toBeFalse();
});
