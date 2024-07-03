<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Federation;
use Exception;
use Illuminate\Support\Facades\Storage;

class EntityService
{
    /**
     * Save to federation Folder using federation-id
     *
     * @throws Exception if federation doesnt exist in database
     */
    public function saveMetadataToFederationFolder($entity_id, $federation_id): void
    {
        $federation = Federation::find($federation_id);

        if (! $federation) {
            throw new Exception("Federation $federation_id not found");
        }
        $this->saveEntityMetadataToFolder($entity_id, $federation->name);
    }

    /**
     * save entity if we know folder name
     *
     * @throws Exception if entity doesnt exist in database
     */
    public function saveEntityMetadataToFolder($entity_id, $folderName): void
    {
        $entity = Entity::find($entity_id);
        if (! $entity) {
            throw new Exception("Entity not found with id $entity_id");
        }
        $fileName = $entity->file;
        if (! Storage::disk('metadata')->exists($folderName)) {
            Storage::disk('metadata')->makeDirectory($folderName);
        }
        $filePath = $folderName.'/'.$fileName;
        $content = $entity->xml_file;
        Storage::disk('metadata')->put($filePath, $content);

    }
}
