<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\User;
use App\Notifications\EntityAddedToHfd;
use App\Notifications\EntityAddedToRs;
use App\Notifications\EntityDeletedFromHfd;
use App\Notifications\EntityDeletedFromRs;
use App\Notifications\EntityUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public static function sendModelNotification(Model $model, $notification): void
    {
        if (! method_exists($model, 'operators')) {
            throw new \InvalidArgumentException('The given model does not have an operators relationship.');
        }

        $operators = $model->operators;

        self::sendOperatorNotification($operators, $notification);
    }

    public static function sendOperatorNotification(Collection $operators, $notification): void
    {
        if ($notification == null) {
            return;
        }

        $admins = User::activeAdmins()->select('id', 'email')->get();

        $operatorIds = $operators->pluck('id');

        $filteredAdmins = $admins->filter(function ($admin) use ($operatorIds) {
            return ! $operatorIds->contains($admin->id);
        });

        Notification::sendNow($operators, $notification);

        Notification::sendNow($filteredAdmins, $notification);
    }

    private static function sendRsNotification(Entity $entity): bool
    {
        if ($entity->wasChanged('rs')) {

            if ($entity->rs == 1) {
                self::sendModelNotification($entity, new EntityAddedToRs($entity));
            } else {
                self::sendModelNotification($entity, new EntityDeletedFromRs($entity));
            }

            return true;
        }

        return false;
    }

    private static function sendHfDNotification(Entity $entity): bool
    {
        if ($entity->wasChanged('hfd')) {

            if ($entity->hfd) {
                self::sendModelNotification($entity, new EntityAddedToHfd($entity));
            } else {
                self::sendModelNotification($entity, new EntityDeletedFromHfd($entity));
            }

            return true;
        }

        return false;
    }

    public static function sendUpdateNotification(Entity $entity): void
    {

        if (! self::sendRsNotification($entity) && ! self::sendHfDNotification($entity)) {
            self::sendModelNotification($entity, new EntityUpdated($entity));
        }

    }
}
