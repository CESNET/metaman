<?php
namespace App\Traits;
use App\Facades\EntityFacade;
use App\Models\Entity;

trait EdugainTrait{

    use FederationTrait;

    public function  makeEdu2Edugain()
    {
        $folderName = 'edu2edugain';
        $eduFed = Entity::where('edugain',1 )->get();

        foreach ($eduFed as $edu) {
            EntityFacade::saveEntityMetadataToFolder($edu->id, $folderName);

        }



    }




}
