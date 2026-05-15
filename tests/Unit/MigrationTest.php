<?php

use Illuminate\Support\Facades\Schema;

it('creates the permissions table', function () {
    expect(Schema::hasTable('portier_permissions'))->toBeTrue()
        ->and(Schema::hasColumns('portier_permissions', [
            'id', 'name', 'display_name', 'group', 'description', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

it('creates the roles table', function () {
    expect(Schema::hasTable('portier_roles'))->toBeTrue()
        ->and(Schema::hasColumns('portier_roles', [
            'id', 'name', 'display_name', 'description', 'parent_id', 'is_system', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

it('creates the role_permissions pivot table', function () {
    expect(Schema::hasTable('portier_role_permissions'))->toBeTrue()
        ->and(Schema::hasColumns('portier_role_permissions', [
            'role_id', 'permission_id', 'granted',
        ]))->toBeTrue();
});

it('creates the user_roles pivot table', function () {
    expect(Schema::hasTable('portier_user_roles'))->toBeTrue()
        ->and(Schema::hasColumns('portier_user_roles', [
            'user_id', 'user_type', 'role_id',
        ]))->toBeTrue();
});

it('creates the user_permissions pivot table', function () {
    expect(Schema::hasTable('portier_user_permissions'))->toBeTrue()
        ->and(Schema::hasColumns('portier_user_permissions', [
            'user_id', 'user_type', 'permission_id', 'granted',
        ]))->toBeTrue();
});
