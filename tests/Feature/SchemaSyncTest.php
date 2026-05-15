<?php

use Illuminate\Support\Facades\Event;
use Portier\Events\PermissionsSynced;
use Portier\Models\Permission;
use Portier\Services\SchemaResolver;

// SchemaResolver tests

it('resolves grouped permissions', function () {
    $resolver = new SchemaResolver;

    $result = $resolver->resolve([
        'posts' => ['create', 'read', 'update', 'delete'],
    ]);

    expect($result)->toBe(['posts.create', 'posts.read', 'posts.update', 'posts.delete']);
});

it('resolves nested grouped permissions', function () {
    $resolver = new SchemaResolver;

    $result = $resolver->resolve([
        'posts' => ['comments' => ['create', 'delete']],
    ]);

    expect($result)->toBe(['posts.comments.create', 'posts.comments.delete']);
});

it('resolves flat string permissions', function () {
    $resolver = new SchemaResolver;

    $result = $resolver->resolve([
        'reports.export',
        'system.backup',
    ]);

    expect($result)->toBe(['reports.export', 'system.backup']);
});

it('resolves mixed formats', function () {
    $resolver = new SchemaResolver;

    $result = $resolver->resolve([
        'posts' => ['create', 'read'],
        'reports.export',
    ]);

    expect($result)->toBe(['posts.create', 'posts.read', 'reports.export']);
});

it('deduplicates permissions', function () {
    $resolver = new SchemaResolver;

    $result = $resolver->resolve([
        'posts' => ['create', 'create'],
    ]);

    expect($result)->toBe(['posts.create']);
});

it('reads from config when no argument passed', function () {
    config(['portier.schema' => ['users' => ['create', 'delete']]]);

    $resolver = new SchemaResolver;
    $result = $resolver->resolve();

    expect($result)->toBe(['users.create', 'users.delete']);
});

// SyncPermissionsCommand tests

it('creates missing permissions', function () {
    config(['portier.schema' => ['posts' => ['create', 'read']]]);

    $this->artisan('portier:sync')
        ->expectsOutputToContain('Created 2 permission(s)')
        ->assertSuccessful();

    expect(Permission::pluck('name')->sort()->values()->all())
        ->toBe(['posts.create', 'posts.read']);
});

it('does not duplicate existing permissions', function () {
    Permission::create(['name' => 'posts.create']);
    config(['portier.schema' => ['posts' => ['create', 'read']]]);

    $this->artisan('portier:sync')
        ->expectsOutputToContain('Created 1 permission(s)')
        ->assertSuccessful();

    expect(Permission::count())->toBe(2);
});

it('reports nothing to sync when up to date', function () {
    Permission::create(['name' => 'posts.create']);
    config(['portier.schema' => ['posts' => ['create']]]);

    $this->artisan('portier:sync')
        ->expectsOutputToContain('Nothing to sync')
        ->assertSuccessful();
});

it('dry run shows what would change without applying', function () {
    config(['portier.schema' => ['posts' => ['create', 'read']]]);

    $this->artisan('portier:sync', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Would create')
        ->assertSuccessful();

    expect(Permission::count())->toBe(0);
});

it('removes orphans when flag is set', function () {
    Permission::create(['name' => 'posts.create']);
    Permission::create(['name' => 'old.permission']);
    config(['portier.schema' => ['posts' => ['create']]]);

    $this->artisan('portier:sync', ['--remove-orphans' => true])
        ->expectsOutputToContain('Removed 1 orphan(s)')
        ->assertSuccessful();

    expect(Permission::pluck('name')->all())->toBe(['posts.create']);
});

it('does not remove orphans without flag', function () {
    Permission::create(['name' => 'old.permission']);
    config(['portier.schema' => ['posts' => ['create']]]);

    $this->artisan('portier:sync')->assertSuccessful();

    expect(Permission::where('name', 'old.permission')->exists())->toBeTrue();
});

it('dry run shows orphans', function () {
    Permission::create(['name' => 'old.permission']);
    config(['portier.schema' => ['posts' => ['create']]]);

    $this->artisan('portier:sync', ['--dry-run' => true])
        ->expectsOutputToContain('Orphans')
        ->assertSuccessful();
});

it('fires PermissionsSynced event', function () {
    Event::fake();
    config(['portier.schema' => ['posts' => ['create']]]);

    $this->artisan('portier:sync')->assertSuccessful();

    Event::assertDispatched(PermissionsSynced::class, function ($event) {
        return $event->created === ['posts.create'] && $event->removed === [];
    });
});

it('fires PermissionsSynced event with removed permissions', function () {
    Event::fake();
    Permission::create(['name' => 'old.permission']);
    config(['portier.schema' => []]);

    $this->artisan('portier:sync', ['--remove-orphans' => true])->assertSuccessful();

    Event::assertDispatched(PermissionsSynced::class, function ($event) {
        return $event->created === [] && $event->removed === ['old.permission'];
    });
});
