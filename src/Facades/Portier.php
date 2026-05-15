<?php

namespace Portier\Facades;

use Illuminate\Support\Facades\Facade;

class Portier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'portier';
    }
}
