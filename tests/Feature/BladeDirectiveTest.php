<?php

use Illuminate\Support\Facades\Blade;
use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
});

it('@role directive renders content when user has role', function () {
    Role::create(['name' => 'admin']);
    $this->user->assignRole('admin');
    $this->user->load('roles');

    $this->actingAs($this->user);

    $rendered = Blade::render('@role("admin") VISIBLE @endrole');

    expect(trim($rendered))->toBe('VISIBLE');
});

it('@role directive hides content when user lacks role', function () {
    $this->actingAs($this->user);

    $rendered = Blade::render('@role("admin") VISIBLE @endrole');

    expect(trim($rendered))->toBe('');
});

it('@permission directive renders content when user has permission', function () {
    $role = Role::create(['name' => 'editor']);
    Permission::create(['name' => 'posts.create']);
    $role->permissions()->attach(Permission::first(), ['granted' => true]);
    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    $this->actingAs($this->user);

    $rendered = Blade::render('@permission("posts.create") VISIBLE @endpermission');

    expect(trim($rendered))->toBe('VISIBLE');
});

it('@permission directive hides content when user lacks permission', function () {
    $this->actingAs($this->user);

    $rendered = Blade::render('@permission("posts.create") VISIBLE @endpermission');

    expect(trim($rendered))->toBe('');
});

it('@role directive hides content for guests', function () {
    auth()->logout();

    $rendered = Blade::render('@role("admin") VISIBLE @endrole');

    expect(trim($rendered))->toBe('');
});

it('@permission directive hides content for guests', function () {
    auth()->logout();

    $rendered = Blade::render('@permission("posts.create") VISIBLE @endpermission');

    expect(trim($rendered))->toBe('');
});
