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
        $data = $user->loadCount(['followers', 'following'])->append('birth_date');

        return [
            'status' => 200,
            'data' => $data,
        ];
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
        if (in_array($type, ['likedPosts', 'likedComments', 'bookmarks'])) {
            $query = $user->{$type}()->orderByPivot('created_at', 'desc');
        }

        if ($type === 'posts') {
            $query = $user->posts()->orderByDesc('created_at');
        }

        $data = $query->withPaginated();

        return array_merge($data, [
            'status' => 200,
        ]);
    }

    /**
     * Get paginated comments with the parent post.
     * 
     * @param \App\Models\User  $user
     * @return array
     */
    public function getComments(User $user): array
    {
        $data = Post::whereHas('comments', fn($q) => $q->where('user_id', $user->id))
                    ->with(['comments' => fn($q) => $q->orderByDesc('created_at')])
                    ->withPaginated();

        return array_merge($data, [
            'status' => 200,
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
        $data = $user->{$type}()->withPaginated(20, config('api.response.user.basic'));

        return array_merge($data, [
            'status' => 200,
        ]);
    }
}