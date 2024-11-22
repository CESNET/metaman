<?php

namespace App\Notifications;

use App\Models\Federation;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FederationMembersChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public Federation $federation,
        public Collection $entities,
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
            ->subject(__("notifications.federation_members_{$this->action}_subject"))
            ->markdown('emails.notifications.FederationMembersChanged', [
                'action' => $this->action,
                'name' => $this->federation->name,
                'entities' => $this->entities,
            ]);
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
            'body' => __("notifications.federation_members_{$this->action}_body", [
                'name' => $this->federation->name,
            ]),
            'federation_id' => $this->federation->id,
        ];
    }
}
