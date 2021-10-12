<?php

namespace App\Services;

use App\Models\{User, Notification};

class NotificationService
{
    /**
     * Peek at all newly received notifications.
     * 
     * @param \App\Models\User  $user
     * @return array
     */
    public function peek(User $user): array
    {
        $user->notifications()->where('peeked_at', null)->update(['peeked_at' => now()]);

        return ['status' => 200];
    }

    /**
     * Update an unread notification's status into read.
     * 
     * @param \App\Models\Notification  $notification
     * @return array
     */
    public function readOne(Notification $notification): array
    {
        if ($notification->notifiable->id !== auth()->id()) {
            return [
                'status' => 403,
                'message' => 'Unauthorized.',
            ];
        }

        $notification->markAsRead();

        return ['status' => 200];
    }

    /**
     * Update all unread notifications' status into read.
     * 
     * @param \App\Models\User  $user
     * @return array
     */
    public function readAll(User $user): array
    {
        $user->unreadNotifications->markAsRead();

        return ['status' => 200];
    }
}