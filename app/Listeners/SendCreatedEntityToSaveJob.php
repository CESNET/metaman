<?php

namespace App\Listeners;

use App\Events\CreateEntity;
use App\Jobs\EduGainAddEntity;
use App\Jobs\FolderAddEntity;

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

        if ($event->entity->approved == 1) {
            FolderAddEntity::dispatch($event->entity);

            if ($event->entity->edugain == 1) {
                EduGainAddEntity::dispatch($event->entity);
            }

        }
    }
}
