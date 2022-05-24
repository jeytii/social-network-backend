<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Http\Request;

class ProfileRepository
{
    /**
     * Get paginated posts or commments.
     * 
     * @param \App\Models\User  $user
     * @param string  $type
     * @return array
     */
    public function get(User $user, string $type): array
    {
        return $user->{$type}()->latest()->withPaginated();
    }

    /**
     * Get paginated posts or comments that the user liked, commented on, or bookmarked.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param string  $type
     * @return array
     */
    public function getPostsOrComments(Request $request, string $type): array
    {
        return $request->user()->{$type}()
            ->orderByPivot('created_at', 'desc')
            ->withPaginated();
    }

    /**
     * Get paginated followers or followed users.
     *
     * @param \App\Models\User  $user
     * @param string  $type
     * @return array
     */
    public function getUserConnections(User $user, string $type): array
    {
        return $user->{$type}()
            ->orderByPivot('created_at', 'desc')
            ->withPaginated(20, config('response.user'));
    }
}
