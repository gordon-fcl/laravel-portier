<?php

use Portier\Models\Permission;
use Portier\Models\Role;

it('can create a permission', function () {
    $permission = Permission::create([
        'name' => 'posts.create',
        'display_name' => 'Create Posts',
        'group' => 'posts',
        'description' => 'Allows creating posts',
    ]);

    expect($permission)->toBeInstanceOf(Permission::class)
        ->and($permission->name)->toBe('posts.create')
        ->and($permission->display_name)->toBe('Create Posts')
        ->and($permission->group)->toBe('posts');
});

it('uses the configured table name', function () {
    $permission = new Permission;

    expect($permission->getTable())->toBe('portier_permissions');
});

it('enforces unique permission names', function () {
    Permission::create(['name' => 'posts.create']);

    Permission::create(['name' => 'posts.create']);
})->throws(\Illuminate\Database\QueryException::class);

it('belongs to many roles', function () {
    $permission = Permission::create(['name' => 'posts.create']);
    $role = Role::create(['name' => 'editor']);

    $role->permissions()->attach($permission, ['granted' => true]);

    expect($permission->roles)->toHaveCount(1)
        ->and($permission->roles->first()->name)->toBe('editor')
        ->and($permission->roles->first()->pivot->granted)->toBeTrue();
});
