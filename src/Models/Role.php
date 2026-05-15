<?php

namespace Portier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('portier.table_prefix', 'portier_').'roles';
    }

    public function permissions(): BelongsToMany
    {
        $prefix = config('portier.table_prefix', 'portier_');

        return $this->belongsToMany(Permission::class, $prefix.'role_permissions')
            ->using(RolePermission::class)
            ->withPivot('granted');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
