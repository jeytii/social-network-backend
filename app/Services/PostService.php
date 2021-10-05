<?php

namespace App\Services;

use App\Models\{User, Post};
use Illuminate\Http\Request;
use App\Notifications\NotifyUponAction;

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
            'message' => 'Successfully created a post.',
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

        return [
            'status' => 200,
            'message' => 'Successfully updated a post.',
        ];
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

        return [
            'status' => 200,
            'message' => 'Successfully deleted a post.',
        ];
    }

    /**
     * Like a post.
     * 
     * @param \App\Models\User  $liker
     * @param \App\Models\Post  $post
     * @return array
     */
    public function likePost(User $liker, Post $post): array
    {
        $liker->likes()->attach($post->id);
        
        $post->user->notify(new NotifyUponAction(
            $liker,
            config('api.notifications.post_liked'),
            "/posts/{$post->slug}"
        ));

        return [
            'status' => 200,
            'message' => 'Successfully liked a post.',
        ];
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
        $user->likes()->detach($postId);

        return [
            'status' => 200,
            'message' => 'Successfully disliked a post.',
        ];
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

        return [
            'status' => 200,
            'message' => 'Successfully bookmarked a post.',
        ];
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

        return [
            'status' => 200,
            'message' => 'Successfully unbookmarked a post.',
        ];
    }
}