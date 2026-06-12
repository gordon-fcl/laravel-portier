<?php

namespace Portier\Console;

use Illuminate\Console\Command;
use Portier\Models\Permission;
use Portier\Models\Role;

class CreateRoleCommand extends Command
{
    protected $signature = 'portier:create-role
        {name : The role name (slug)}
        {--display= : Human-readable display name}
        {--permissions=* : Permissions to attach (by name)}';

    protected $description = 'Create a new Portier role';

    public function handle(): int
    {
        /** @var string $name */
        $name = $this->argument('name');

        if (Role::where('name', $name)->exists()) {
            $this->error("Role '{$name}' already exists.");

            return self::FAILURE;
        }

        $role = Role::create([
            'name' => $name,
            'display_name' => $this->option('display') ?: null,
        ]);

        $permissions = $this->option('permissions');
        if ($permissions) {
            $ids = Permission::whereIn('name', $permissions)->pluck('id');
            $role->permissions()->attach($ids, ['granted' => true]);

            $this->info("Role '{$name}' created with ".count($ids).' permission(s).');
        } else {
            $this->info("Role '{$name}' created.");
        }

        return self::SUCCESS;
    }
}
