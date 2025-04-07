<?php

namespace App\Traits;

use App\Facades\EntityFacade;
use App\Models\Entity;

trait EdugainTrait
{
    /**
     * @codeCoverageIgnore
     */
    public function makeEdu2Edugain(): void
    {
        $folderName = config('storageCfg.edu2edugain');
        $eduFed = Entity::where('edugain', 1)->get();

        foreach ($eduFed as $edu) {
            EntityFacade::saveEntityMetadataToFolder($edu->id, $folderName);
        }

    }
}
