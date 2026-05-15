<?php

namespace Portier\Console;

use Illuminate\Console\Command;
use Portier\Events\PermissionsSynced;
use Portier\Models\Permission;
use Portier\Services\SchemaResolver;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'portier:sync
        {--dry-run : Show what would change without applying}
        {--remove-orphans : Remove permissions not in the schema}';

    protected $description = 'Sync permissions from config schema to database';

    public function handle(SchemaResolver $resolver): int
    {
        $schemaPermissions = $resolver->resolve();
        $existingPermissions = Permission::pluck('name')->all();

        $toCreate = array_diff($schemaPermissions, $existingPermissions);
        $orphans = array_diff($existingPermissions, $schemaPermissions);

        if (empty($toCreate) && (empty($orphans) || ! $this->option('remove-orphans'))) {
            $this->info('Nothing to sync — database is up to date.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->dryRun($toCreate, $orphans);

            return self::SUCCESS;
        }

        foreach ($toCreate as $name) {
            Permission::create(['name' => $name]);
        }

        $removed = [];
        if ($this->option('remove-orphans') && ! empty($orphans)) {
            Permission::whereIn('name', $orphans)->delete();
            $removed = array_values($orphans);
        }

        $created = array_values($toCreate);

        if (! empty($created)) {
            $this->info('Created '.count($created).' permission(s): '.implode(', ', $created));
        }

        if (! empty($removed)) {
            $this->warn('Removed '.count($removed).' orphan(s): '.implode(', ', $removed));
        }

        PermissionsSynced::dispatch($created, $removed);

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $toCreate
     * @param  array<int|string, string>  $orphans
     */
    private function dryRun(array $toCreate, array $orphans): void
    {
        if (! empty($toCreate)) {
            $this->info('[dry-run] Would create: '.implode(', ', $toCreate));
        }

        if (! empty($orphans)) {
            $this->warn('[dry-run] Orphans (use --remove-orphans to delete): '.implode(', ', $orphans));
        }
    }
}
