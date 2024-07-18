<?php

namespace App\Listeners;

use App\Events\UpdateEntity;
use App\Jobs\EduGainAddEntity;
use App\Jobs\EduGainDeleteEntity;
use App\Jobs\FolderAddEntity;
use App\Services\NotificationService;
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

        $entity = $event->entity;

        if ($entity->wasChanged('xml_file')
        ) {
            FolderAddEntity::dispatch($event->entity);
        } elseif ($entity->approved == 1 && ! $entity->wasChanged('edugain')) {
            Log::info('update some  entity in SendUpdatedEntityToSaveJob');
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
