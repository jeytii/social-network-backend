<?php

namespace App\Repositories;

use App\Models\{User, Post};
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProfileRepository
{
    /**
     * Get a specific user.
     * 
     * @param string  $username
     * @return \App\Models\User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    private function getUser(string $username): User
    {
        return User::withCount('followers', 'following')
                    ->where('username', $username)
                    ->firstOrFail();
    }

    /**
     * Get the data of a specific user.
     * 
     * @param string  $username
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function get(string $username): array
    {
        try {
            $user = $this->getUser($username);
            $data = $user->append('birth_date');
            $message = 'Successfully retrieved the profile info.';
            $status = 200;

            return compact('status', 'message', 'data');
        }
        catch (ModelNotFoundException $exception) {
            return [
                'status' => 404,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Get paginated posts according to the type.
     * 
     * @param string  $username
     * @param string  $type
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getPosts(string $username, string $type): array
    {
        try {
            $user = $this->getUser($username);

            if (in_array($type, ['likes', 'bookmarks'])) {
                $query = $user->{$type}()->orderByPivot('created_at', 'desc');
            }
    
            if ($type === 'posts') {
                $query = $user->posts()->orderByDesc('created_at');
            }
    
            $data = $query->withPaginated();
    
            return array_merge($data, [
                'status' => 200,
                'message' => 'Successfully retrieved posts.',
            ]);
        }
        catch (ModelNotFoundException $exception) {
            return [
                'status' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Get paginated comments with the parent post.
     * 
     * @param string  $username
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getComments(string $username): array
    {
        try {
            $user = $this->getUser($username);
            $data = Post::whereHas('comments', fn($q) => $q->where('user_id', $user->id))
                    ->with(['comments' => fn($q) => $q->orderByDesc('created_at')])
                    ->withPaginated();

            return array_merge($data, [
                'status' => 200,
                'message' => 'Successfully retrieved comments.',
            ]);
        }
        catch (ModelNotFoundException $exception) {
            return [
                'status' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Get paginated followers or followed users.
     *
     * @param string  $username
     * @param string  $type
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getConnections(string $username, string $type): array
    {
        try {
            $user = $this->getUser($username);
            $data = $user->{$type}()->withPaginated(
                        20,
                        array_merge(config('api.response.user.basic'), ['slug'])
                    );
    
            return array_merge($data, [
                'status' => 200,
                'message' => 'Successfully retrieved users.',
            ]);
        }
        catch (ModelNotFoundException $exception) {
            return [
                'status' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];
        }
    }
}