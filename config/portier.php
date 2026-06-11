<?php

return [
    'user_models' => [
        'default' => 'App\\Models\\User',
    ],

    'table_prefix' => 'portier_',

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'store' => null,
    ],

    'role_inheritance' => true,

    'direct_overrides_role' => true,

    'super_admin_role' => 'super-admin',

    'ui' => [
        'enabled' => true,
        'prefix' => 'admin/portier',
        'middleware' => ['web', 'auth'],
        'guard' => 'web',
        'gate' => 'manage-portier',
    ],

    'api' => [
        'enabled' => true,
        'middleware' => ['api', 'auth:sanctum'],
        'prefix' => 'api/portier',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Schema
    |--------------------------------------------------------------------------
    |
    | Define your permissions here and run `php artisan portier:sync` to keep
    | the database in sync. Supports grouped and flat formats:
    |
    | 'posts' => ['create', 'read', 'update', 'delete'],
    |
    */
    'schema' => [
        'users' => ['create', 'read', 'update', 'delete'],
        'roles' => ['create', 'read', 'update', 'delete', 'assign'],
        'permissions' => ['read', 'assign'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    |
    | Suggested roles to create with `portier:sync`. These are not enforced
    | — just a starting point. Adjust to suit your application.
    |
    */
    'roles' => [
        'super-admin' => ['*'],
        'admin' => [
            'users.*', 'roles.read', 'roles.assign', 'permissions.read', 'permissions.assign',
        ],
        'readonly' => [
            'users.read', 'roles.read', 'permissions.read',
        ],
    ],
];
