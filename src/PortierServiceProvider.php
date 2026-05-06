<?php

namespace Portier;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Portier\Middleware\PermissionMiddleware;
use Portier\Middleware\RoleMiddleware;

class PortierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/portier.php', 'portier');
    }

    public function boot(): void
    {
        $this->publishConfig();
        $this->loadMigrations();
        $this->registerGateHook();
        $this->registerMiddleware();
        $this->registerBladeDirectives();
    }

    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/portier.php' => config_path('portier.php'),
        ], 'portier-config');
    }

    private function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function registerGateHook(): void
    {
        Gate::before(function (Authorizable $user, string $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }

    private function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('role', RoleMiddleware::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
    }

    private function registerBladeDirectives(): void
    {
        Blade::if('role', function (string $role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        Blade::if('permission', function (string $permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });
    }
}
