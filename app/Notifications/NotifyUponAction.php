<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\{DatabaseMessage, BroadcastMessage};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

class NotifyUponAction extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\User  $user
     * @param int  $action
     * @param string  $url
     * @return void
     */
    public function __construct(User $user, int $action, string $url)
    {
        $this->user = $user;
        $this->action = $action;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
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
            'url' => $this->url,
        ]);
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        $notifications = $notifiable->notifications();

        return new BroadcastMessage([
            'count' => $notifications->unpeeked()->count(),
            'data' => $notifications->first(),
        ]);
    }
}
