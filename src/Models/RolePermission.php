<?php

namespace Portier\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RolePermission extends Pivot
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'granted' => 'boolean',
    ];
}
