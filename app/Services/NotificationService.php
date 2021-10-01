<?php

namespace App\Services;

use App\Models\User;

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

        return [
            'status' => 200,
            'message' => 'Successfully peeked at new notifications.'
        ];
    }

    /**
     * Update an unread notification's status into read.
     * 
     * @param \App\Models\User  $user
     * @param string  $id
     * @return array
     */
    public function readOne(User $user, string $id): array
    {
        $user->notifications()->firstWhere('id', $id)->markAsRead();

        return [
            'status' => 200,
            'message' => 'Successfully marked an unread notification as read.'
        ];
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

        return [
            'status' => 200,
            'message' => 'Successfully marked all unread notifications as read.',
        ];
    }
}