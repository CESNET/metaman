<?php

namespace App\Notifications;

use App\Models\Entity;
use App\Models\Federation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FederationMemberChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public Federation $federation,
        public Entity $entity,
        public string $action
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
            ->subject(__("notifications.federation_member_{$this->action}_subject"))
            ->line(__("notifications.federation_member_{$this->action}_body", [
                'name' => $this->federation->name,
                'entity' => $this->entity->name_en,
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
            'body' => __("notifications.federation_member_{$this->action}_body", [
                'name' => $this->federation->name,
                'entity' => $this->entity->name_en,
            ]),
            'federation_id' => $this->federation->id,
        ];
    }
}
