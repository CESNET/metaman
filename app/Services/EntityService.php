<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Federation;
use Exception;
use Illuminate\Support\Facades\Log;
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
        $diskName = config('storageCfg.name');

        $entity = Entity::find($entity_id);
        if (! $entity) {
            throw new Exception("Entity not found with id $entity_id");
        }
        $fileName = $entity->file;
        if (! Storage::disk($diskName)->exists($folderName)) {
            Storage::disk($diskName)->makeDirectory($folderName);
        }
        $filePath = $folderName.'/'.$fileName;
        $content = $entity->xml_file;
        Storage::disk($diskName)->put($filePath, $content);

    }

    public function deleteEntityMetadataFromFolder($fileName, $folderName): void
    {
        $diskName = config('storageCfg.name');
        $pathToFile = $folderName.'/'.$fileName;

        if (Storage::disk($diskName)->exists($pathToFile)) {
            try {
                Storage::disk($diskName)->delete($pathToFile);
            } catch (Exception $e) {
                Log::error("Failed to delete file: {$pathToFile}. Error: ".$e->getMessage());
            }
        } else {
            Log::warning("File does not exist: {$pathToFile}");
        }

    }
}
