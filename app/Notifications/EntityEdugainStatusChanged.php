<?php

namespace App\Notifications;

use App\Models\Entity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EntityEdugainStatusChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public Entity $entity
    ) {
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
            ->subject(__('notifications.entity_edugain_status_changed_subject'))
            ->line(__('notifications.entity_edugain_status_changed_body', [
                'name' => is_null($this->entity->name_en) ? $this->entity->entityid : $this->entity->name_en,
                'status' => $this->entity->edugain ? __('common.edugain_is_member') : __('common.edugain_is_no_longer_member'),
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
            'body' => __('notifications.entity_edugain_status_changed_body', [
                'name' => is_null($this->entity->name_en) ? $this->entity->entityid : $this->entity->name_en,
                'status' => $this->entity->edugain ? __('common.edugain_is_member') : __('common.edugain_is_no_longer_member'),
            ]),
            'entity_id' => $this->entity->id,
        ];
    }
}
