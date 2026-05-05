<?php

use Portier\Models\Permission;
use Portier\Models\Role;

it('can create a role', function () {
    $role = Role::create([
        'name' => 'admin',
        'display_name' => 'Administrator',
        'description' => 'Full access',
    ]);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->name)->toBe('admin')
        ->and($role->display_name)->toBe('Administrator');
});

it('uses the configured table name', function () {
    $role = new Role;

    expect($role->getTable())->toBe('portier_roles');
});

it('casts is_system to boolean', function () {
    $role = Role::create(['name' => 'admin', 'is_system' => true]);

    expect($role->is_system)->toBeTrue()->toBeBool();
});

it('enforces unique role names', function () {
    Role::create(['name' => 'admin']);

    Role::create(['name' => 'admin']);
})->throws(\Illuminate\Database\QueryException::class);

it('belongs to many permissions with granted pivot', function () {
    $role = Role::create(['name' => 'editor']);
    $perm1 = Permission::create(['name' => 'posts.create']);
    $perm2 = Permission::create(['name' => 'posts.delete']);

    $role->permissions()->attach($perm1, ['granted' => true]);
    $role->permissions()->attach($perm2, ['granted' => false]);

    $role->refresh();

    expect($role->permissions)->toHaveCount(2);

    $granted = $role->permissions->firstWhere('name', 'posts.create');
    $denied = $role->permissions->firstWhere('name', 'posts.delete');

    expect($granted->pivot->granted)->toBeTrue()
        ->and($denied->pivot->granted)->toBeFalse();
});

it('supports parent-child hierarchy', function () {
    $parent = Role::create(['name' => 'manager']);
    $child = Role::create(['name' => 'team-lead', 'parent_id' => $parent->id]);

    expect($child->parent->name)->toBe('manager')
        ->and($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->name)->toBe('team-lead');
});

it('allows null parent for top-level roles', function () {
    $role = Role::create(['name' => 'admin']);

    expect($role->parent)->toBeNull()
        ->and($role->parent_id)->toBeNull();
});

it('sets parent to null when parent is deleted', function () {
    $parent = Role::create(['name' => 'manager']);
    $child = Role::create(['name' => 'team-lead', 'parent_id' => $parent->id]);

    $parent->delete();
    $child->refresh();

    expect($child->parent_id)->toBeNull();
});
