<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserStatusChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public User $user
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
            ->subject(__('notifications.user_status_changed_subject'))
            ->line(__('notifications.user_status_changed_body', [
                'name' => $this->user->name,
                'status' => $this->user->active ? strtolower(__('common.active')) : strtolower(__('common.inactive')),
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
            'body' => __('notifications.user_status_changed_body', [
                'name' => $this->user->name,
                'status' => $this->user->active ? strtolower(__('common.active')) : strtolower(__('common.inactive')),
            ]),
            'user_id' => $this->user->id,
        ];
    }
}
