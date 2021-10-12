<?php

namespace App\Services;

use App\Models\{User, Post, Comment};
use Illuminate\Http\Request;
use Illuminate\Support\{Collection, Str};
use Illuminate\Support\Facades\{DB, Notification};
use App\Notifications\NotifyUponAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class CommentService
{
    /**
     * Make a notification upon commenting.
     * 
     * @param \App\Models\User  $notifier
     * @param string  $notificationType
     * @param string  $postSlug
     * @return mixed
     */
    private function notifyOnComment(User $notifier, string $notificationType, string $postSlug)
    {
        return new NotifyUponAction(
            $notifier,
            config('constants.notifications.' . $notificationType),
            "/posts/{$postSlug}"
        );
    }

    /**
     * Get all mentioned users in the comment body.
     * 
     * @param string  $body
     * @return \Illuminate\Support\Collection
     */
    private function getMentionedUsers(string $body): Collection
    {
        $mentions = Str::of($body)->matchAll('/@[a-zA-Z0-9_]+/');
        $usernames = $mentions->map(fn($mention) => Str::replace('@', '', $mention))->toArray();
        
        return User::whereIn('username', $usernames)->get();
    }

    /**
     * Create a comment.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function createComment(Request $request): array
    {
        try {
            $post = Post::where('slug', $request->pid)->firstOrFail();
            $mentionedUsers = $this->getMentionedUsers($request->body);

            $comment = DB::transaction(function() use ($request, $post, $mentionedUsers) {
                $comment = $request->user()->comments()
                                ->create([
                                    'post_id' => $post->id,
                                    'body' => $request->body,
                                ])
                                ->first();
    
                if (!$mentionedUsers->contains('username', $post->user->username)) {
                    $post->user->notify($this->notifyOnComment($request->user(), 'commented_on_post', $request->pid));
                }
    
                Notification::send(
                    $mentionedUsers,
                    $this->notifyOnComment($request->user(), 'mentioned_on_comment', $request->pid)
                );

                return $comment;
            });

            return [
                'status' => 201,
                'data' => $comment,
            ];
        }
        catch (ModelNotFoundException $exception) {
            return [
                'status' => 404,
                'message' => 'Post not found.',
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
     * Update a comment.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function updateComment(Request $request, string $commentId): array
    {
        $request->user()
            ->comments()
            ->find($commentId)
            ->update($request->only('body'));

        return ['status' => 200];
    }

    /**
     * Delete a comment.
     * 
     * @param \App\Models\User  $user
     * @param string  $commentId
     * @return array
     */
    public function deleteComment(User $user, string $commentId): array
    {
        $user->comments()->find($commentId)->delete();

        return ['status' => 200];
    }

    /**
     * Like a comment.
     * 
     * @param \App\Models\User  $liker
     * @param \App\Models\Comment  $comment
     * @return array
     * @throws \Exception
     */
    public function likeComment(User $liker, Comment $comment): array
    {
        try {
            DB::transaction(function() use ($liker, $comment) {
                $liker->likedComments()->attach($comment->id);
                
                $comment->user->notify(new NotifyUponAction(
                    $liker,
                    config('constants.notifications.comment_liked'),
                    "/posts/{$comment->post->slug}"
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
     * Dislike a comment.
     * 
     * @param \App\Models\User  $user
     * @param string  $commentId
     * @return array
     */
    public function dislikeComment(User $user, string $commentId): array
    {
        $user->likedComments()->detach($commentId);

        return ['status' => 200];
    }
}