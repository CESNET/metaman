<?php

namespace App\Notifications;

use App\Models\Entity;
use App\Models\Federation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EntityRequested extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public Entity $entity,
        public Federation $federation
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
            ->subject(__('notifications.entity_requested_subject'))
            ->line(__('notifications.entity_requested_body', [
                'name' => $this->entity->name_en,
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
            'body' => __('notifications.entity_requested_body', [
                'name' => $this->entity->name_en,
                'federation' => $this->federation->name,
            ]),
            'federation_id' => $this->federation->id,
        ];
    }
}
