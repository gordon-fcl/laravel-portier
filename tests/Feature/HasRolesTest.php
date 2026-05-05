<?php

use Portier\Models\Role;
use Portier\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
});

it('can assign a role by name', function () {
    Role::create(['name' => 'editor']);

    $this->user->assignRole('editor');

    expect($this->user->roles)->toHaveCount(1)
        ->and($this->user->roles->first()->name)->toBe('editor');
});

it('can assign a role by model instance', function () {
    $role = Role::create(['name' => 'editor']);

    $this->user->assignRole($role);

    expect($this->user->roles)->toHaveCount(1);
});

it('can assign multiple roles at once', function () {
    Role::create(['name' => 'editor']);
    Role::create(['name' => 'viewer']);

    $this->user->assignRole('editor', 'viewer');

    expect($this->user->roles)->toHaveCount(2);
});

it('does not duplicate roles on reassignment', function () {
    Role::create(['name' => 'editor']);

    $this->user->assignRole('editor');
    $this->user->assignRole('editor');

    expect($this->user->roles)->toHaveCount(1);
});

it('can remove a role', function () {
    Role::create(['name' => 'editor']);

    $this->user->assignRole('editor');
    $this->user->removeRole('editor');
    $this->user->load('roles');

    expect($this->user->roles)->toHaveCount(0);
});

it('can remove multiple roles at once', function () {
    Role::create(['name' => 'editor']);
    Role::create(['name' => 'viewer']);

    $this->user->assignRole('editor', 'viewer');
    $this->user->removeRole('editor', 'viewer');
    $this->user->load('roles');

    expect($this->user->roles)->toHaveCount(0);
});

it('can sync roles', function () {
    Role::create(['name' => 'editor']);
    Role::create(['name' => 'viewer']);
    Role::create(['name' => 'admin']);

    $this->user->assignRole('editor', 'viewer');
    $this->user->syncRoles(['admin', 'viewer']);
    $this->user->load('roles');

    expect($this->user->roles)->toHaveCount(2)
        ->and($this->user->roles->pluck('name')->sort()->values()->all())->toBe(['admin', 'viewer']);
});

it('checks if user has a role', function () {
    Role::create(['name' => 'editor']);

    $this->user->assignRole('editor');

    expect($this->user->hasRole('editor'))->toBeTrue()
        ->and($this->user->hasRole('admin'))->toBeFalse();
});

it('checks if user has any of given roles', function () {
    Role::create(['name' => 'editor']);

    $this->user->assignRole('editor');

    expect($this->user->hasAnyRole(['editor', 'admin']))->toBeTrue()
        ->and($this->user->hasAnyRole(['admin', 'viewer']))->toBeFalse();
});

it('throws when assigning a non-existent role', function () {
    $this->user->assignRole('non-existent');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
