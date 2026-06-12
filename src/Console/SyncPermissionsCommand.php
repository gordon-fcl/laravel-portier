<?php

namespace Portier\Console;

use Illuminate\Console\Command;
use Portier\Events\PermissionsSynced;
use Portier\Models\Permission;
use Portier\Models\Role;
use Portier\Services\SchemaResolver;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'portier:sync
        {--dry-run : Show what would change without applying}
        {--remove-orphans : Remove permissions not in the schema}';

    protected $description = 'Sync permissions and roles from config schema to database';

    public function handle(SchemaResolver $resolver): int
    {
        $created = $this->syncPermissions($resolver);
        $removed = $this->removeOrphans($resolver);
        $this->syncRoles();

        if (empty($created) && empty($removed)) {
            $this->info('Nothing to sync — database is up to date.');

            return self::SUCCESS;
        }

        PermissionsSynced::dispatch($created, $removed);

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function syncPermissions(SchemaResolver $resolver): array
    {
        $schemaPermissions = $resolver->resolve();
        $existingPermissions = Permission::pluck('name')->all();
        $toCreate = array_values(array_diff($schemaPermissions, $existingPermissions));

        if (empty($toCreate)) {
            return [];
        }

        if ($this->option('dry-run')) {
            $this->info('[dry-run] Would create permissions: '.implode(', ', $toCreate));

            return [];
        }

        foreach ($toCreate as $name) {
            Permission::create(['name' => $name]);
        }

        $this->info('Created '.count($toCreate).' permission(s): '.implode(', ', $toCreate));

        return $toCreate;
    }

    /**
     * @return list<string>
     */
    private function removeOrphans(SchemaResolver $resolver): array
    {
        $schemaPermissions = $resolver->resolve();
        $existingPermissions = Permission::pluck('name')->all();
        $orphans = array_values(array_diff($existingPermissions, $schemaPermissions));

        if (empty($orphans)) {
            return [];
        }

        if ($this->option('dry-run')) {
            $this->warn('Orphans found: '.implode(', ', $orphans));

            return [];
        }

        if (! $this->option('remove-orphans')) {
            return [];
        }

        Permission::whereIn('name', $orphans)->delete();
        $this->warn('Removed '.count($orphans).' orphan(s): '.implode(', ', $orphans));

        return $orphans;
    }

    private function syncRoles(): void
    {
        /** @var array<string, list<string>> $roles */
        $roles = config('portier.roles', []);

        if (empty($roles)) {
            return;
        }

        foreach ($roles as $roleName => $permissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            $permissionIds = [];
            foreach ($permissionNames as $name) {
                $permission = Permission::where('name', $name)->first();
                if ($permission) {
                    $permissionIds[$permission->id] = ['granted' => true];
                }
            }

            if (! empty($permissionIds)) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }

            if ($this->option('dry-run')) {
                $this->info("[dry-run] Would sync role '{$roleName}' with: ".implode(', ', $permissionNames));
            } else {
                $this->info("Synced role '{$roleName}'.");
            }
        }
    }
}
