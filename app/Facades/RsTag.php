<?php

namespace App\Facades;

use App\Models\Entity;
use App\Services\RsTagService;
use DOMXPath;
use Illuminate\Support\Facades\Facade;

/**
 * Class RsTagFacade
 *
 * @method static string create(Entity $entity)
 * @method static void delete(Entity $entity)
 * @method static void update(Entity $entity)
 * @method static bool hasResearchAndScholarshipTag(string $xml_document)
 * @method static void deleteByXpath( DOMXPath $xPath)
 */
class RsTag extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RsTagService::class;
    }
}
