<?php

namespace Portier\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PermissionsSynced
{
    use Dispatchable;

    /**
     * @param  list<string>  $created
     * @param  list<string>  $removed
     */
    public function __construct(
        public readonly array $created,
        public readonly array $removed,
    ) {}
}
