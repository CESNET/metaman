<?php
namespace App\Services;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Support\Facades\Notification;


class NotificationService{

    public static function sendEntityNotification(Entity $entity,$notification){
        $admins = User::activeAdmins()->select('id', 'email')->get();

        $operators = $entity->operators->pluck('id')->toArray();

        $filteredAdmins = $admins->filter(function ($admin) use ($operators) {
            return !in_array($admin->id, $operators);
        });

        Notification::sendNow($entity->operators, new $notification($entity));
        Notification::sendNow($filteredAdmins, new $notification($entity));
    }

}
