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
        } elseif ($entity->approved == 1 && ! $entity->wasChanged('approved')) {

            if (! $entity->wasChanged('edugain') && ! $entity->wasChanged('deleted_at')) {
                NotificationService::sendUpdateNotification($entity);
            }
        }
        if ($entity->wasChanged('edugain')) {
            if ($entity->edugain == 1) {
                EdugainAddEntity::dispatch($entity);
            } else {
                EdugainDeleteEntity::dispatch($entity);
            }
        }
    }

    /**
     * Handle the Entity "deleted" event.
     */
    public function deleted(Entity $entity): void
    {
        $ent = Entity::withTrashed()->find($entity->id);
        if ($ent) {
            $federationIDs = $entity->federations->pluck('id')->toArray();

            FolderDeleteEntity::dispatch($entity->id, $federationIDs, $entity->file);

            if ($entity->edugain == 1) {
                EdugainDeleteEntity::dispatch($entity);
            }
        }
    }

    /**
     * Handle the Entity "restored" event.
     */
    public function restored(Entity $entity): void
    {
        if ($entity->approved == 1) {
            FolderAddEntity::dispatch($entity);

            if ($entity->edugain == 1) {
                EdugainAddEntity::dispatch($entity);
            }
        }
    }
}
