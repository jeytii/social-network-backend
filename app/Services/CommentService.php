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
     * Create a comment.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Exception
     */
    public function createComment(Request $request): array
    {
        $post = Post::firstWhere('slug', $request->input('pid'));
        $mentionedUsers = $this->getMentionedUsers($request->input('body'));
        $user = $request->user();

        try {
            $comment = DB::transaction(function() use ($request, $user, $post, $mentionedUsers) {
                $comment = $user->comments()->create([
                                'post_id' => $post->id,
                                'body' => $request->input('body')
                            ]);
    
                if (!$mentionedUsers->count() || !$mentionedUsers->contains('username', $post->user->username)) {
                    $post->user->notify($this->notifyOnComment(
                        $user,
                        NotificationModel::COMMENTED_ON_POST,
                        $request->input('pid')
                    ));
                }

                if ($mentionedUsers->count()) {
                    Notification::send(
                        $mentionedUsers,
                        $this->notifyOnComment(
                            $user,
                            NotificationModel::MENTIONED_ON_COMMENT,
                            $request->input('pid')
                        )
                    );
                }

                return Comment::find($comment->id);
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
     * @param \App\Models\Comment  $comment
     * @return array
     */
    public function updateComment(Request $request, Comment $comment): array
    {
        $comment->update($request->only('body'));

        return ['status' => 200];
    }

    /**
     * Delete a comment.
     * 
     * @param \App\Models\Comment  $comment
     * @return array
     */
    public function deleteComment(Comment $comment): array
    {
        $comment->delete();

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
                $actionType = NotificationModel::LIKED_COMMENT;

                $liker->likedComments()->attach($comment);
                
                $comment->user->notify(new NotifyUponAction($liker, $actionType, "/posts/{$comment->post->slug}"));
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