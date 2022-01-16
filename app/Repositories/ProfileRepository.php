<?php

namespace App\Repositories;

use App\Models\{User, Post};
use Illuminate\Http\Request;

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
        $data = $user->loadCount(['followers', 'following', 'posts', 'comments'])
                    ->makeVisible('birth_date');

        return [
            'status' => 200,
            'data' => $data,
        ];
    }

    /**
     * Get paginated posts or commments.
     * 
     * @param \App\Models\User  $user
     * @param string  $type
     * @return array
     */
    public function getPostsOrComments(User $user, string $type): array
    {
        $data = $user->{$type}()->latest()->withPaginated();

        return array_merge($data, [
            'status' => 200,
        ]);
    }

    /**
     * Get paginated posts and comments that the user liked, commented on, or bookmarked.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param string  $type
     * @return array
     */
    public function getInteractedPosts(Request $request, string $type): array
    {
        $data = $request->user()->{$type}()
                        ->orderByPivot('created_at', 'desc')
                        ->withPaginated();

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
        $data = $user->{$type}()
                    ->orderByPivot('created_at', 'desc')
                    ->withPaginated(20, config('api.response.user.basic'));

        return array_merge($data, [
            'status' => 200,
        ]);
    }
}
