<?php

namespace Portier\Console;

use Illuminate\Console\Command;
use Portier\Services\PermissionRegistrar;

class ClearCacheCommand extends Command
{
    protected $signature = 'portier:clear-cache';

    protected $description = 'Clear the Portier permission and role cache';

    public function handle(PermissionRegistrar $registrar): int
    {
        $registrar->forgetCachedPermissions();

        $this->info('Portier permission cache cleared successfully.');

        return self::SUCCESS;
    }
}
