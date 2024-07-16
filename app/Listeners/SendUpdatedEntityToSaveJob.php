<?php

namespace App\Listeners;

use App\Events\UpdateEntity;
use App\Jobs\EduGainAddEntity;
use App\Jobs\EduGainDeleteEntity;
use App\Jobs\FolderAddEntity;
use App\Notifications\EntityAddedToHfd;
use App\Notifications\EntityAddedToRs;
use App\Notifications\EntityDeletedFromHfd;
use App\Notifications\EntityDeletedFromRs;
use App\Notifications\EntityUpdated;
use App\Services\NotificationService;

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

        $entity = $event->entity;

        if ($entity->wasChanged('xml_file') ||
            ($entity->wasChanged('approved') && $entity->approved == 1)
        ) {
            FolderAddEntity::dispatch($event->entity);
        } elseif ($entity->approved == 1 && ! $entity->wasChanged('edugain')) {
            NotificationService::sendUpdateNotification($entity);
        }
        if ($entity->wasChanged('edugain')) {
            if ($entity->edugain == 1) {
                EduGainAddEntity::dispatch($entity);
            } else {
                EduGainDeleteEntity::dispatch($entity);
            }
        }

    }
}
