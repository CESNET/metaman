<?php

namespace App\Facades;

use App\Models\Entity;
use App\Services\HfdTagService;
use DOMXPath;
use Illuminate\Support\Facades\Facade;

/**
 * Class HfdTag facade
 *
 * @method static false|string create(Entity $entity)
 * @method static void delete(Entity $entity)
 * @method static false|string createFromXml(string $xml_document)
 * @method static void deleteByXpath( DOMXPath $xPath)
 * @method static false|string update(Entity $entity)
 */
class HfdTag extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HfdTagService::class;
    }
}