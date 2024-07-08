<?php

namespace App\Listeners;

use App\Events\UpdateEntity;
use App\Jobs\FolderAddEntity;
use Illuminate\Support\Facades\Log;

class SendUpdatedEntityToSaveJob
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
    public function handle(UpdateEntity $event): void
    {
     //   Log::info('Listener triggered for UpdateEntity event', ['entity_id' => $event->entity->id]);
        $ent = $event->entity;

        if ($ent->wasChanged('xml_file')) {
            FolderAddEntity::dispatch($event->entity);
        }
    }
}
