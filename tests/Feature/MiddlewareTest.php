<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Portier\Middleware\PermissionMiddleware;
use Portier\Middleware\RoleMiddleware;
use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Tests\Fixtures\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
    $this->next = fn () => new Response('OK');
});

// RoleMiddleware

it('role middleware passes when user has role', function () {
    Role::create(['name' => 'admin']);
    $this->user->assignRole('admin');
    $this->user->load('roles');

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $response = (new RoleMiddleware)->handle($request, $this->next, 'admin');

    expect($response->getContent())->toBe('OK');
});

it('role middleware throws 403 when user lacks role', function () {
    Role::create(['name' => 'admin']);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    (new RoleMiddleware)->handle($request, $this->next, 'admin');
})->throws(HttpException::class);

it('role middleware supports any operator (|)', function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'editor']);
    $this->user->assignRole('editor');
    $this->user->load('roles');

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $response = (new RoleMiddleware)->handle($request, $this->next, 'admin|editor');

    expect($response->getContent())->toBe('OK');
});

it('role middleware supports all operator (&)', function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'editor']);
    $this->user->assignRole('admin');
    $this->user->load('roles');

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    (new RoleMiddleware)->handle($request, $this->next, 'admin&editor');
})->throws(HttpException::class);

it('role middleware all operator passes when user has all roles', function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'editor']);
    $this->user->assignRole('admin', 'editor');
    $this->user->load('roles');

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $response = (new RoleMiddleware)->handle($request, $this->next, 'admin&editor');

    expect($response->getContent())->toBe('OK');
});

it('role middleware throws 403 for unauthenticated user', function () {
    $request = Request::create('/test');
    $request->setUserResolver(fn () => null);

    (new RoleMiddleware)->handle($request, $this->next, 'admin');
})->throws(HttpException::class);

// PermissionMiddleware

it('permission middleware passes when user has permission', function () {
    $role = Role::create(['name' => 'editor']);
    Permission::create(['name' => 'posts.create']);
    $role->permissions()->attach(Permission::first(), ['granted' => true]);
    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $response = (new PermissionMiddleware)->handle($request, $this->next, 'posts.create');

    expect($response->getContent())->toBe('OK');
});

it('permission middleware throws 403 when user lacks permission', function () {
    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    (new PermissionMiddleware)->handle($request, $this->next, 'posts.create');
})->throws(HttpException::class);

it('permission middleware supports any operator (|)', function () {
    $role = Role::create(['name' => 'editor']);
    Permission::create(['name' => 'posts.update']);
    $role->permissions()->attach(Permission::first(), ['granted' => true]);
    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $response = (new PermissionMiddleware)->handle($request, $this->next, 'posts.create|posts.update');

    expect($response->getContent())->toBe('OK');
});

it('permission middleware supports all operator (&)', function () {
    $role = Role::create(['name' => 'editor']);
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'posts.update']);
    $role->permissions()->attach(Permission::where('name', 'posts.create')->first(), ['granted' => true]);
    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    (new PermissionMiddleware)->handle($request, $this->next, 'posts.create&posts.update');
})->throws(HttpException::class);

it('permission middleware all operator passes when user has all permissions', function () {
    $role = Role::create(['name' => 'editor']);
    $p1 = Permission::create(['name' => 'posts.create']);
    $p2 = Permission::create(['name' => 'posts.update']);
    $role->permissions()->attach($p1, ['granted' => true]);
    $role->permissions()->attach($p2, ['granted' => true]);
    $this->user->assignRole('editor');
    $this->user->load('roles.permissions');

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $this->user);

    $response = (new PermissionMiddleware)->handle($request, $this->next, 'posts.create&posts.update');

    expect($response->getContent())->toBe('OK');
});
