<?php

namespace Portier\Console;

use Illuminate\Console\Command;
use Portier\Services\PermissionRegistrar;

class CacheCommand extends Command
{
    protected $signature = 'portier:cache';

    protected $description = 'Warm the Portier permission and role cache';

    public function handle(PermissionRegistrar $registrar): int
    {
        $registrar->warmCache();

        $this->info('Portier permission cache warmed successfully.');

        return self::SUCCESS;
    }
}
