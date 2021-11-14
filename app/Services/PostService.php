<?php

namespace App\Services;

use App\Models\{User, Post, Notification as NotificationModel};
use Illuminate\Http\Request;
use Illuminate\Support\{Collection, Str};
use Illuminate\Support\Facades\{DB, Notification};
use App\Notifications\NotifyUponAction;
use Exception;

class PostService
{
    /**
     * Get all mentioned users in the comment body.
     * 
     * @param string  $body
     * @return \Illuminate\Support\Collection
     */
    private function getMentionedUsers(string $body): Collection
    {
        $mentions = Str::of($body)->matchAll('/@[a-zA-Z0-9_]+/');

        if (!$mentions->count()) {
            return collect([]);
        }

        $usernames = $mentions->unique()->reduce(function($list, $mention) {
            if ($mention !== '@' . auth()->user()->username) {
                array_push($list, Str::replace('@', '', $mention));
            }

            return $list;
        }, []);
        
        return User::whereIn('username', $usernames)->get();
    }

    /**
     * Create a post.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function createPost(Request $request): array
    {
        try {
            $data = DB::transaction(function() use ($request) {
                $post = $request->user()->posts()->create($request->only('body'));
                $mentionedUsers = $this->getMentionedUsers($request->input('body'));

                if ($mentionedUsers->count()) {
                    Notification::send(
                        $mentionedUsers,
                        new NotifyUponAction(
                            $request->user(),
                            NotificationModel::MENTIONED_ON_POST,
                            "/posts/{$request->input('pid')}"
                        )
                    );
                }

                return $request->user()->posts()->find($post->id);
            });

            return [
                'status' => 201,
                'data' => $data,
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
                    NotificationModel::LIKED_POST,
                    "/posts/{$post->slug}"
                ));
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
     * Dislike a post.
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
