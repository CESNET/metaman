<?php
namespace App\Services;
use App\Models\Entity;
use App\Models\Federation;
use Illuminate\Support\Facades\Storage;

class EntityService
{
    public function SaveEntityMetadataToFile($entity_id,$federation_id)
    {
        $entity = Entity::find($entity_id);
        $federation = Federation::find($federation_id);

        if(!$entity || !$federation){
            return;
        }
        $folderName = $federation->name;
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
