<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyUponAction extends Notification
{
    use Queueable;

    public $user;

    public $action;

    public $path;

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
        return [
            'user' => $this->user->only(['name', 'gender', 'image_url']),
            'action' => $this->action,
            'path' => $this->path,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'count' => $notifiable->notifications()->whereNull('peeked_at')->count()
        ]);
    }

    /**
     * Get the type of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastType()
    {
        return 'action.notification';
    }
}
