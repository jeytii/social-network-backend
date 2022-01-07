<?php

namespace App\Repositories;

use App\Models\User;

class NotificationRepository
{
    /**
     * Get the paginated notifications.
     * 
     * @param \App\Models\User  $user
     * @return array
     */
    public function get(User $user): array
    {
        $data = $user->notifications()->withPaginated();

        return array_merge($data, [
            'status' => 200,
        ]);
    }

    /**
     * Get the number of new notifications.
     * 
     * @param \App\Models\User  $user
     * @return array
     */
    public function getCount(User $user): array
    {
        $data = $user->notifications()->unpeeked()->count();

        return [
            'status' => 200,
            'data' => $data,
        ];
    }
}