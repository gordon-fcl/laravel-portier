<?php

namespace Portier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('portier.table_prefix', 'portier_').'permissions';
    }

    public function roles(): BelongsToMany
    {
        $prefix = config('portier.table_prefix', 'portier_');

        return $this->belongsToMany(Role::class, $prefix.'role_permissions')
            ->using(RolePermission::class)
            ->withPivot('granted');
    }
}
