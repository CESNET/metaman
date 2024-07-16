<?php

namespace App\Listeners;

use App\Events\DeleteEntity;
use App\Jobs\EduGainDeleteEntity;
use App\Jobs\FolderDeleteEntity;

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

        if (! $entity->isForceDeleting()) {
            FolderDeleteEntity::dispatch($event->entity);

            if ($event->entity->edugain == 1) {
                EduGainDeleteEntity::dispatch($event->entity);
            }
        }

    }
}
