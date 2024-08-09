<?php

namespace App\Facades;

use App\Models\Entity;
use App\Services\CategoryTagService;
use Illuminate\Support\Facades\Facade;

/**
 * Class CategoryTag facade
 *
 * @method static false|string create(Entity $entity)
 * @method static void delete(Entity $entity)
 */
class CategoryTag extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CategoryTagService::class;
    }
}
