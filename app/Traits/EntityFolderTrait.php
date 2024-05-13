<?php
namespace App\Traits;



use App\Facades\EntityFacade;
use App\Models\Membership;

trait EntityFolderTrait{

    use FederationTrait;

    public function  createAllMetadataFiles() : void
    {
        $this->updateFederationFolders();
        $membership = Membership::select('entity_id','federation_id')->whereApproved(1)->get();
        foreach ($membership as $member) {
            EntityFacade::SaveEntityMetadataToFile($member->entity_id, $member->federation_id);
        }


    }



}
