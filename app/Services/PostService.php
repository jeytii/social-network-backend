<?php

namespace App\Services;

use App\Models\{User, Post, Notification as NotificationModel};
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
        $post = $request->user()->posts()->create($request->only('body'));
        $data = $request->user()->posts()->find($post->id);
        
        return [
            'status' => 201,
            'data' => $data,
        ];
    }

    /**
     * Update an existing post.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Post  $post
     * @return array
     */
    public function updatePost(Request $request, Post $post): array
    {
        if ($post->body === $request->only('body')) {
            return [
                'status' => 401,
                'message' => 'No changes made.',
            ];
        }

        $post->update($request->only('body'));

        return ['status' => 200];
    }

    /**
     * Delete an existing post.
     * 
     * @param \App\Models\Post  $post
     * @return array
     */
    public function deletePost(Post $post): array
    {
        $post->delete();

        return ['status' => 200];
    }

    /**
     * Add the post to the list of likes.
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
                $actionType = NotificationModel::LIKED_POST;

                $liker->likedPosts()->attach($post);
                
                $post->user->notify(new NotifyUponAction($liker, $actionType, "/posts/{$post->slug}"));
            });

            return [
                'status' => 200,
                'data' => $post->likers()->count(),
            ];
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
    }

    /**
     * Remove the post from the list of likes.
     * 
     * @param \App\Models\User  $user
     * @param \App\Models\Post  $post
     * @return array
     */
    public function dislikePost(User $user, Post $post): array
    {
        $user->likedPosts()->detach($post);

        return [
            'status' => 200,
            'data' => $post->likers()->count(),
        ];
    }

    /**
     * Add the post to the list of bookmarks.
     * 
     * @param \App\Models\User  $user
     * @param \App\Models\Post  $post
     * @return array
     */
    public function bookmarkPost(User $user, Post $post): array
    {
        $user->bookmarks()->attach($post);

        return ['status' => 200];
    }

    /**
     * Remove the post from the list of bookmarks.
     * 
     * @param \App\Models\User  $user
     * @param \App\Models\Post  $post
     * @return array
     */
    public function unbookmarkPost(User $user, Post $post): array
    {
        $user->bookmarks()->detach($post);

        return ['status' => 200];
    }
}
