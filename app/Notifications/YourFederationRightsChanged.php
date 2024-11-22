<?php

namespace App\Notifications;

use App\Models\Federation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class YourFederationRightsChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public Federation $federation,
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
            ->subject(__("notifications.your_federation_rights_{$this->action}_subject"))
            ->line(__("notifications.your_federation_rights_{$this->action}_body", [
                'name' => $this->federation->name,
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
            'body' => __("notifications.your_federation_rights_{$this->action}_body", [
                'name' => $this->federation->name,
            ]),
            'federation_id' => $this->federation->id,
        ];
    }
}
