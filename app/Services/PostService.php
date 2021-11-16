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
        $mentionedUsers = $this->getMentionedUsers($request->input('body'));

        try {
            $data = DB::transaction(function() use ($request, $mentionedUsers) {
                $post = $request->user()->posts()->create($request->only('body'));
                $actionType = NotificationModel::MENTIONED_ON_POST;

                if ($mentionedUsers->count()) {
                    Notification::send(
                        $mentionedUsers,
                        new NotifyUponAction($request->user(), $actionType, "/posts/{$request->input('post')}")
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
     * Update an existing post.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Post  $post
     * @return array
     */
    public function updatePost(Request $request, Post $post): array
    {
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
