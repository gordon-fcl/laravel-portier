<?php

namespace Portier\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Portier\Traits\Authorisable;

class User extends Authenticatable
{
    use Authorisable;

    protected $guarded = [];
    protected $table = 'users';
}
