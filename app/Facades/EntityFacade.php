<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class EntityFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'entity';
    }
}
