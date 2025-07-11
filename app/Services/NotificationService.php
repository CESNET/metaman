<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\User;
use App\Notifications\EntityAddedToHfd;
use App\Notifications\EntityAddedToRs;
use App\Notifications\EntityDeletedFromHfd;
use App\Notifications\EntityDeletedFromRs;
use App\Notifications\EntityEdugainStatusChanged;
use App\Notifications\EntityUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification as NotificationsNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class NotificationService
{
    public static function sendModelNotification(Model $model, NotificationsNotification $notification): void
    {
        throw_unless(
            method_exists($model, 'operators'),
            InvalidArgumentException::class,
            'The given model does not have an operators relationship.'
        );

        self::sendOperatorNotification($model->operators, $notification);
    }

    public static function sendOperatorNotification(Collection $operators, NotificationsNotification $notification): void
    {
        $admins = User::activeAdmins()->select('id', 'email')->get();

        Notification::send($operators, $notification);
        Notification::send($admins->diff($operators), $notification);
    }

    private static function sendRsNotification(Entity $entity): bool
    {
        if ($entity->wasChanged('rs')) {
            $entity->rs
                ? self::sendModelNotification($entity, new EntityAddedToRs($entity))
                : self::sendModelNotification($entity, new EntityDeletedFromRs($entity));

            return true;
        }

        return false;
    }

    private static function sendHfdNotification(Entity $entity): bool
    {
        if ($entity->wasChanged('hfd')) {
            $entity->hfd
                ? self::sendModelNotification($entity, new EntityAddedToHfd($entity))
                : self::sendModelNotification($entity, new EntityDeletedFromHfd($entity));

            return true;
        }

        return false;
    }

    private static function sendEdugainNotification(Entity $entity): bool
    {
        if ($entity->wasChanged('edugain')) {
            self::sendModelNotification($entity, new EntityEdugainStatusChanged($entity));

            return true;
        }

        return false;
    }

    public static function sendUpdateNotification(Entity $entity): void
    {
        if (
            ! self::sendRsNotification($entity) &&
            ! self::sendHfdNotification($entity) &&
            ! self::sendEdugainNotification($entity)
        ) {
            self::sendModelNotification($entity, new EntityUpdated($entity));
        }
    }
}
