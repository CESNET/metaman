<?php
namespace App\Services;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Support\Facades\Notification;


class NotificationService{

    public static function sendEntityNotification(Entity $entity,$notification){
        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::sendNow($entity->operators, new $notification($entity));
        Notification::sendNow($admins, new $notification($entity));
    }

}
