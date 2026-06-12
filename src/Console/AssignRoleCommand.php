<?php

namespace Portier\Console;

use Illuminate\Console\Command;
use Portier\Models\Role;

class AssignRoleCommand extends Command
{
    protected $signature = 'portier:assign
        {user : The user ID}
        {role : The role name to assign}
        {--model= : User model class (uses default from config)}';

    protected $description = 'Assign a role to a user';

    public function handle(): int
    {
        $modelClass = $this->option('model')
            ?: config('portier.user_models.default', 'App\\Models\\User');

        /** @var string $userId */
        $userId = $this->argument('user');

        $user = $modelClass::find($userId);

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        /** @var string $roleName */
        $roleName = $this->argument('role');

        if (! Role::where('name', $roleName)->exists()) {
            $this->error("Role '{$roleName}' does not exist.");

            return self::FAILURE;
        }

        $user->assignRole($roleName);

        $this->info("Role '{$roleName}' assigned to user #{$user->getKey()}.");

        return self::SUCCESS;
    }
}
