<?php

namespace App\Repositories;

use App\Models\{User, Notification};

class NotificationRepository
{
    /**
     * Format a notification
     * 
     * @param mixed  $data
     * @return \App\Models\Notification
     */
    private function formatNotification($data): Notification
    {
        $message = null;

        if ($data->action === Notification::FOLLOWED) {
            $message = "{$data->user['name']} followed you.";
        }
        
        if ($data->action === Notification::LIKED_POST) {
            $message = "{$data->user['name']} liked your post.";
        }

        if ($data->action === Notification::LIKED_COMMENT) {
            $message = "{$data->user['name']} liked your comment.";
        }

        if ($data->action === Notification::MENTIONED_ON_POST) {
            $pronoun = $data->user['gender'] === 'Male' ? 'his' : 'her';
            $message = "{$data->user['name']} mentioned you on {$pronoun} post.";
        }

        if ($data->action === Notification::MENTIONED_ON_COMMENT) {
            $pronoun = $data->user['gender'] === 'Male' ? 'his' : 'her';
            $message = "{$data->user['name']} mentioned you on {$pronoun} comment.";
        }

        if ($data->action === Notification::COMMENTED_ON_POST) {
            $message = "{$data->user['name']} commented on your post.";
        }

        $data->message = $message;
        $data->path = config('app.client_url') . $data->path;

        return $data;
    }

    /**
     * Get the paginated notifications.
     * 
     * @param \App\Models\User  $user
     * @return array
     */
    public function get(User $user): array
    {
        $notifications = $user->notifications()->withPaginated();
        $items = array_map([$this, 'formatNotification'], $notifications['items']);

        return [
            'items' => $items,
            'has_more' => $notifications['has_more'],
            'next_offset' => $notifications['next_offset'],
            'status' => 200,
        ];
    }
}