<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class EntityFacade
 *
 * @method static void saveMetadataToFederationFolder($entity_id, $federation_id)
 * @method static void saveEntityMetadataToFolder($entity_id, $folderName)
 * @method static void deleteEntityMetadataFromFolder($fileName, $folderName)
 */
class EntityFacade extends Facade
{
    /**
     * @codeCoverageIgnore
     */
    protected static function getFacadeAccessor()
    {
        return 'entity';
    }
}
