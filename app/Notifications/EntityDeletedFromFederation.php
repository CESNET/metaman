<?php

namespace App\Notifications;

use App\Models\Entity;
use App\Models\Federation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EntityDeletedFromFederation extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Entity $entity, Federation $federation)
    {
        $this->entity = $entity;
        $this->federation = $federation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject(__('notifications.entity_deleted_from_federation_subject'))
                    ->line(__('notifications.entity_deleted_from_federation_body', [
                        'name' => $this->entity->name_en ?? $this->entity->entityid,
                        'federation' => $this->federation->name,
                    ]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
