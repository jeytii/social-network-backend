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
        if ($data->action === config('api.notifications.user_followed')) {
            $data->message = "{$data->user['name']} followed you.";
        }
        
        if ($data->action === config('api.notifications.post_liked')) {
            $data->message = "{$data->user['name']} liked your post.";
        }

        if ($data->action === config('api.notifications.commented_on_post')) {
            $data->message = "{$data->user['name']} commented on your post.";
        }

        if ($data->action === config('api.notifications.mentioned_on_comment')) {
            $pronoun = $data->user['gender'] === 'Male' ? 'his' : 'her';
            $data->message = "{$data->user['name']} mentioned you on {$pronoun} comment.";
        }

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
            'message' => 'Successfully retrieved the notifications.',
        ];
    }
}