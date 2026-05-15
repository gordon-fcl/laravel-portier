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

    'schema' => [],
];
