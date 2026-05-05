<?php

namespace Portier;

use Illuminate\Support\ServiceProvider;

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
}
