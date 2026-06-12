<?php

use Illuminate\Support\Facades\Event;
use Portier\Events\PermissionGranted;
use Portier\Events\PermissionRevoked;
use Portier\Events\RoleAssigned;
use Portier\Events\RoleCreated;
use Portier\Events\RoleDeleted;
use Portier\Events\RoleRemoved;
use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
});

// --- Event dispatching tests ---

it('fires RoleAssigned when assigning a role', function () {
    Event::fake(RoleAssigned::class);
    Role::create(['name' => 'editor']);

    $this->user->assignRole('editor');

    Event::assertDispatched(RoleAssigned::class, function ($event) {
        return $event->user->is($this->user) && $event->role->name === 'editor';
    });
});

it('fires RoleRemoved when removing a role', function () {
    Event::fake(RoleRemoved::class);
    Role::create(['name' => 'editor']);
    $this->user->roles()->attach(Role::first());

    $this->user->removeRole('editor');

    Event::assertDispatched(RoleRemoved::class, function ($event) {
        return $event->user->is($this->user) && $event->role->name === 'editor';
    });
});

it('fires PermissionGranted when granting a permission', function () {
    Event::fake(PermissionGranted::class);
    Permission::create(['name' => 'posts.create']);

    $this->user->grantPermission('posts.create');

    Event::assertDispatched(PermissionGranted::class, function ($event) {
        return $event->user->is($this->user) && $event->permission->name === 'posts.create';
    });
});

it('fires PermissionRevoked when revoking a permission', function () {
    Event::fake(PermissionRevoked::class);
    $perm = Permission::create(['name' => 'posts.create']);
    $this->user->permissions()->attach($perm, ['granted' => true]);

    $this->user->revokePermission('posts.create');

    Event::assertDispatched(PermissionRevoked::class, function ($event) {
        return $event->user->is($this->user) && $event->permission->name === 'posts.create';
    });
});

it('fires RoleCreated when a role is created', function () {
    Event::fake(RoleCreated::class);

    Role::create(['name' => 'moderator']);

    Event::assertDispatched(RoleCreated::class, function ($event) {
        return $event->role->name === 'moderator';
    });
});

it('fires RoleDeleted when a role is deleted', function () {
    Event::fake(RoleDeleted::class);
    $role = Role::create(['name' => 'temp']);

    $role->delete();

    Event::assertDispatched(RoleDeleted::class, function ($event) {
        return $event->role->name === 'temp';
    });
});

it('fires multiple RoleAssigned events for multiple roles', function () {
    Event::fake(RoleAssigned::class);
    Role::create(['name' => 'editor']);
    Role::create(['name' => 'viewer']);

    $this->user->assignRole('editor', 'viewer');

    Event::assertDispatched(RoleAssigned::class, 2);
});

it('fires multiple PermissionGranted events for multiple permissions', function () {
    Event::fake(PermissionGranted::class);
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);

    $this->user->grantPermission('posts.create', 'posts.update');

    Event::assertDispatched(PermissionGranted::class, 2);
});

// --- Command tests ---

it('portier:create-role creates a role', function () {
    $this->artisan('portier:create-role', ['name' => 'moderator'])
        ->expectsOutputToContain("Role 'moderator' created.")
        ->assertSuccessful();

    expect(Role::where('name', 'moderator')->exists())->toBeTrue();
});

it('portier:create-role fails for duplicate role', function () {
    Role::create(['name' => 'editor']);

    $this->artisan('portier:create-role', ['name' => 'editor'])
        ->expectsOutputToContain('already exists')
        ->assertFailed();
});

it('portier:create-role creates role with display name', function () {
    $this->artisan('portier:create-role', ['name' => 'mod', '--display' => 'Moderator'])
        ->assertSuccessful();

    expect(Role::where('name', 'mod')->first()->display_name)->toBe('Moderator');
});

it('portier:create-role attaches permissions', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);

    $this->artisan('portier:create-role', [
        'name' => 'editor',
        '--permissions' => ['posts.create', 'posts.update'],
    ])->assertSuccessful();

    $role = Role::where('name', 'editor')->first();
    expect($role->permissions)->toHaveCount(2);
});

it('portier:assign assigns a role to a user', function () {
    Role::create(['name' => 'editor']);

    $this->artisan('portier:assign', [
        'user' => $this->user->id,
        'role' => 'editor',
        '--model' => User::class,
    ])->expectsOutputToContain("Role 'editor' assigned")
        ->assertSuccessful();

    $this->user->load('roles');
    expect($this->user->hasRole('editor'))->toBeTrue();
});

it('portier:assign fails for non-existent user', function () {
    Role::create(['name' => 'editor']);

    $this->artisan('portier:assign', [
        'user' => 999,
        'role' => 'editor',
        '--model' => User::class,
    ])->expectsOutputToContain('User not found')
        ->assertFailed();
});

it('portier:assign fails for non-existent role', function () {
    $this->artisan('portier:assign', [
        'user' => $this->user->id,
        'role' => 'missing',
        '--model' => User::class,
    ])->expectsOutputToContain('does not exist')
        ->assertFailed();
});
