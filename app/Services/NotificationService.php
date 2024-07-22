<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\User;
use App\Notifications\EntityAddedToHfd;
use App\Notifications\EntityAddedToRs;
use App\Notifications\EntityDeletedFromHfd;
use App\Notifications\EntityDeletedFromRs;
use App\Notifications\EntityUpdated;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public static function sendEntityNotification(Entity $entity, $notification): void
    {
        if ($notification == null) {
            return;
        }

        $admins = User::activeAdmins()->select('id', 'email')->get();

        $operators = $entity->operators->pluck('id')->toArray();

        $filteredAdmins = $admins->filter(function ($admin) use ($operators) {
            return ! in_array($admin->id, $operators);
        });

        Notification::sendNow($entity->operators, $notification);
        Notification::sendNow($filteredAdmins, $notification);
    }

    private static function sendRsNotification(Entity $entity): bool
    {
        if ($entity->wasChanged('rs')) {

            if ($entity->rs == 1) {
                self::sendEntityNotification($entity, new EntityAddedToRs($entity));
            } else {
                self::sendEntityNotification($entity, new EntityDeletedFromRs($entity));
            }

            return true;
        }

        return false;
    }

    private static function sendHfDNotification(Entity $entity): bool
    {
        if ($entity->wasChanged('hfd')) {

            if ($entity->hfd) {
                self::sendEntityNotification($entity, new EntityAddedToHfd($entity));
            } else {
                self::sendEntityNotification($entity, new EntityDeletedFromHfd($entity));
            }

            return true;
        }

        return false;
    }

    public static function sendUpdateNotification(Entity $entity): void
    {

        if (! self::sendRsNotification($entity) && ! self::sendHfDNotification($entity)) {
            self::sendEntityNotification($entity, new EntityUpdated($entity));
        }

    }
}
