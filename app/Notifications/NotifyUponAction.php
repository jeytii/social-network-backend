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

    /**
     * The user that receives the notification.
     * 
     * @var \App\Models\User
     */
    public $user;

    /**
     * The type of action.
     * 
     * @var int
     */
    public $actionType;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\User  $user
     * @param int  $actionType
     * @return void
     */
    public function __construct(User $user, int $actionType)
    {
        $this->user = $user;
        $this->actionType = $actionType;
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
            'user' => $this->user->only(config('api.response.user.basic')),
            'action' => $this->actionType,
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
            'user' => $this->user->only(config('api.response.user.basic')),
            'action' => $this->actionType,
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
