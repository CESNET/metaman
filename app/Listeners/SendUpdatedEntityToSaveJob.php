<?php

namespace App\Listeners;

use App\Events\UpdateEntity;
use App\Jobs\FolderAddEntity;
use App\Models\User;
use App\Notifications\EntityUpdated;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;

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
        )
        {
            FolderAddEntity::dispatch($event->entity);
        }
        elseif ($entity->approved == 1)
        {
            NotificationService::sendEntityNotification($entity,EntityUpdated::class);
        }
    }
}