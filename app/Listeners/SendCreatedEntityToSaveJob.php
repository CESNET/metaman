<?php

namespace App\Listeners;

use App\Events\CreateEntity;
use App\Jobs\FolderAddEntity;
use Illuminate\Support\Facades\Log;

class SendCreatedEntityToSaveJob
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
    public function handle(CreateEntity $event): void
    {
        //Log::info('Listener triggered for CreateEntity event', ['entity_id' => $event->entity->id]);

        FolderAddEntity::dispatch($event->entity);
    }
}
