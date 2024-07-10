<?php

namespace App\Listeners;

use App\Events\DeleteEntity;
use App\Facades\EntityFacade;
use App\Jobs\FolderDeleteEntity;
use Illuminate\Support\Facades\Log;

class SendDeletedEntityToDeleteJob
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DeleteEntity $event): void
    {
        $entity = $event->entity;
        $federations = $entity->federations;
        $diskName = config('storageCfg.name');

        foreach ($federations as $federation) {
            Log::info("file -> $entity->file folder -> $federation->xml_id");
            EntityFacade::deleteEntityMetadataFromFolder($entity->file, $federation->xml_id);
        }

        //  FolderDeleteEntity::dispatch($event->entity);
    }
}
