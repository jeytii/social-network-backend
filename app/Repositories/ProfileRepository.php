<?php

namespace App\Repositories;

use App\Models\{User, Post};

class ProfileRepository
{
    /**
     * Get the data of a specific user.
     * 
     * @param \App\Models\User  $user
     * @return array
     */
    public function get(User $user): array
    {
        $status = 200;
        $message = 'Successfully retrieved the profile info.';
        $data = $user->append('birth_date');

        return compact('status', 'message', 'data');
    }

    /**
     * Get paginated posts according to the type.
     * 
     * @param \App\Models\User  $user
     * @param string  $type
     * @return array
     */
    public function getPosts(User $user, string $type): array
    {
        if (in_array($type, ['likes', 'bookmarks'])) {
            $query = $user->{$type}()->orderByPivot('created_at', 'desc');
        }

        if ($type === 'posts') {
            $query = $user->posts()->orderByDesc('created_at');
        }

        $data = $query->withUser()
                    ->withCount(['likers as likes_count', 'comments'])
                    ->withPaginated();

        return array_merge($data, [
            'status' => 200,
            'message' => 'Successfully retrieved posts.',
        ]);
    }

    /**
     * Get paginated comments with the parent post.
     * 
     * @param string  $userId
     * @return array
     */
    public function getComments(string $userId): array
    {
        $data = Post::whereHas('comments', fn($q) => $q->where('user_id', $userId))
                    ->withUser()
                    ->with([
                        'comments' => fn($q) => $q->withUser()->orderByDesc('created_at')
                    ])
                    ->withCount(['likers as likes_count', 'comments'])
                    ->withPaginated();

        return array_merge($data, [
            'status' => 200,
            'message' => 'Successfully retrieved comments.',
        ]);
    }

    /**
     * Get paginated followers or followed users.
     *
     * @param \App\Models\User  $user
     * @param string  $type
     * @return array
     */
    public function getConnections(User $user, string $type): array
    {
        $data = $user->{$type}()->withPaginated(
                    20,
                    array_merge(config('api.response.user.basic'), ['slug'])
                );

        return array_merge($data, [
            'status' => 200,
            'message' => 'Successfully retrieved users.',
        ]);
    }
}