<?php
namespace App\Services;
use App\Models\Entity;
use App\Models\Federation;
use Illuminate\Support\Facades\Storage;

class EntityService
{
    public function saveMetadataToFederationFolder($entity_id,$federation_id)
    {
        $federation = Federation::find($federation_id);

        if(!$federation){
            throw new \Exception("Federation $federation_id not found");
        }
        $this->saveEntityMetadataToFolder($entity_id,$federation->name);
    }


    public function saveEntityMetadataToFolder($entity_id,$folderName)
    {
        $entity = Entity::find($entity_id);
        if(!$entity){
            throw new \Exception("Entity not found with id $entity_id");
        }
        $fileName = $entity->file;
        if(!Storage::disk('metadata')->exists($folderName))
        {
            Storage::disk('metadata')->makeDirectory($folderName);
        }
        $filePath = $folderName . '/' . $fileName;
        $content = $entity->xml_file;
        Storage::disk('metadata')->put($filePath, $content);


    }


}
