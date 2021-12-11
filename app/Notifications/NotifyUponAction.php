<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NotifyUponAction extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\User  $user
     * @param int  $action
     * @param string  $path
     * @return void
     */
    public function __construct(User $user, int $action, string $path)
    {
        $this->user = $user;
        $this->action = $action;
        $this->path = $path;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return new DatabaseMessage([
            'user' => $this->user->only(['name', 'gender', 'image_url']),
            'action' => $this->action,
            'path' => $this->path,
        ]);
    }
}
