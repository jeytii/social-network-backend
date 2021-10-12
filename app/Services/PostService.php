<?php

namespace App\Services;

use App\Models\{User, Post};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\NotifyUponAction;
use Exception;

class PostService
{
    /**
     * Create a post.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function createPost(Request $request): array
    {
        $data = $request->user()->posts()->create($request->only('body'))->first();

        return [
            'status' => 201,
            'data' => $data,
        ];
    }

    /**
     * Update a post.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Post  $post
     * @return array
     */
    public function updatePost(Request $request, Post $post): array
    {
        $request->user()->posts()
            ->find($post->id)
            ->update($request->only('body'));

        return ['status' => 200];
    }

    /**
     * Delete a post.
     * 
     * @param \App\Models\User  $user
     * @param string  $postId
     * @return array
     */
    public function deletePost(User $user, string $postId): array
    {
        $user->posts()->find($postId)->delete();

        return ['status' => 200];
    }

    /**
     * Like a post.
     * 
     * @param \App\Models\User  $liker
     * @param \App\Models\Post  $post
     * @return array
     * @throws \Exception
     */
    public function likePost(User $liker, Post $post): array
    {
        try {
            DB::transaction(function() use ($liker, $post) {
                $liker->likedPosts()->attach($post->id);
                
                $post->user->notify(new NotifyUponAction(
                    $liker,
                    config('constants.notifications.post_liked'),
                    "/posts/{$post->slug}"
                ));
            });

            return ['status' => 200];
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
    }

    /**
     * Dislike a post.
     * 
     * @param \App\Models\User  $user
     * @param string  $postId
     * @return array
     */
    public function dislikePost(User $user, string $postId): array
    {
        $user->likedPosts()->detach($postId);

        return ['status' => 200];
    }

    /**
     * Bookmark a post.
     * 
     * @param \App\Models\User  $user
     * @param string  $postId
     * @return array
     */
    public function bookmarkPost(User $user, string $postId): array
    {
        $user->bookmarks()->attach($postId);

        return ['status' => 200];
    }

    /**
     * Unbookmark a post.
     * 
     * @param \App\Models\User  $user
     * @param string  $postId
     * @return array
     */
    public function unbookmarkPost(User $user, string $postId): array
    {
        $user->bookmarks()->detach($postId);

        return ['status' => 200];
    }
}