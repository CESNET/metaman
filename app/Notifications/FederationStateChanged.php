<?php

namespace App\Notifications;

use App\Models\Federation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FederationStateChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
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
            ->subject(__('notifications.federation_state_changed_subject'))
            ->line(__('notifications.federation_state_changed_body', [
                'name' => $this->federation->name,
                'state' => $this->federation->trashed() ? strtolower(__('common.deleted')) : strtolower(__('common.restored')),
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
            'body' => __('notifications.federation_state_changed_body', [
                'name' => $this->federation->name,
                'state' => $this->federation->trashed() ? strtolower(__('common.deleted')) : strtolower(__('common.restored')),
            ]),
            'federation_id' => $this->federation->id,
        ];
    }
}
