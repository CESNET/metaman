<?php

namespace App\Observers;

use App\Jobs\EdugainAddEntity;
use App\Jobs\EdugainDeleteEntity;
use App\Jobs\FolderAddEntity;
use App\Jobs\FolderDeleteEntity;
use App\Models\Entity;
use App\Services\NotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class EntityObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Entity "updated" event.
     */
    public function updated(Entity $entity): void
    {
        if ($entity->wasChanged('xml_file')) {
            FolderAddEntity::dispatch($entity);

            if ($entity->edugain) {
                EdugainAddEntity::dispatch($entity);
            }
        }

        if ($entity->wasChanged('edugain')) {
            $entity->edugain
                ? EdugainAddEntity::dispatch($entity)
                : EdugainDeleteEntity::dispatch($entity);
        }

        if (! $entity->wasChanged('approved')) {
            NotificationService::sendUpdateNotification($entity);
        }
    }

    /**
     * Handle the Entity "deleted" event.
     */
    public function deleted(Entity $entity): void
    {
        FolderDeleteEntity::dispatch($entity);

        if ($entity->edugain) {
            EdugainDeleteEntity::dispatch($entity);
        }
    }

    /**
     * Handle the Entity "restored" event.
     */
    public function restored(Entity $entity): void
    {
        FolderAddEntity::dispatch($entity);

        if ($entity->edugain) {
            EdugainAddEntity::dispatch($entity);
        }
    }
}
