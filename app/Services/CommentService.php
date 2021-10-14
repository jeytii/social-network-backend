<?php

namespace App\Services;

use App\Models\{User, Post, Comment, Notification as NotificationModel};
use Illuminate\Http\Request;
use Illuminate\Support\{Collection, Str};
use Illuminate\Support\Facades\{DB, Notification};
use App\Notifications\NotifyUponAction;
use Exception;

class CommentService
{
    /**
     * Make a notification upon commenting.
     * 
     * @param \App\Models\User  $notifier
     * @param int  $notificationType
     * @param string  $postSlug
     * @return mixed
     */
    private function notifyOnComment(User $notifier, int $notificationType, string $postSlug)
    {
        return new NotifyUponAction($notifier, $notificationType, "/posts/{$postSlug}");
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
        $usernames = $mentions->unique()->reduce(function($list, $mention) {
                        if ($mention !== '@' . auth()->user()->username) {
                            array_push($list, Str::replace('@', '', $mention));
                        }

                        return $list;
                    }, []);
        
        return User::whereIn('username', $usernames)->get();
    }

    /**
     * Create a comment.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Exception
     */
    public function createComment(Request $request): array
    {
        try {
            $comment = DB::transaction(function() use ($request) {
                $postId = $request->input('pid');
                $body = $request->input('body');
                $post = Post::firstWhere('slug', $postId);
                $mentionedUsers = $this->getMentionedUsers($body);
                $comment = $request->user()
                                ->comments()
                                ->create(['post_id' => $post->id, 'body' => $body])
                                ->first();
    
                if (!$mentionedUsers->contains('username', $post->user->username)) {
                    $post->user->notify($this->notifyOnComment(
                        $request->user(),
                        NotificationModel::COMMENTED_ON_POST,
                        $postId
                    ));
                }
    
                Notification::send(
                    $mentionedUsers,
                    $this->notifyOnComment(
                        $request->user(),
                        NotificationModel::MENTIONED_ON_COMMENT,
                        $postId
                    )
                );

                return $comment;
            });

            return [
                'status' => 201,
                'data' => $comment,
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
     * @param string  $commentId
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
                    NotificationModel::LIKED_COMMENT,
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