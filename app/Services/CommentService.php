<?php

namespace App\Services;

use App\Models\{User, Post, Comment, Notification as NotificationModel};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Create a comment.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Exception
     */
    public function createComment(Request $request): array
    {
        $post = Post::firstWhere('slug', $request->input('post'));
        $user = $request->user();

        try {
            $comment = DB::transaction(function() use ($request, $user, $post) {
                $comment = $user->comments()->create([
                                'post_id' => $post->id,
                                'body' => $request->input('body')
                            ]);

                new NotifyUponAction($user, NotificationModel::COMMENTED_ON_POST, "/posts/{$post->slug}");

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
        if ($comment->body === $request->only('body')) {
            return [
                'status' => 401,
                'message' => 'No changes made.',
            ];
        }

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

            return [
                'status' => 200,
                'data' => $comment->likers()->count(),
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
     * Dislike a comment.
     * 
     * @param \App\Models\User  $user
     * @param \App\Models\Comment  $comment
     * @return array
     */
    public function dislikeComment(User $user, Comment $comment): array
    {
        $user->likedComments()->detach($comment);

        return [
            'status' => 200,
            'data' => $comment->likers()->count(),
        ];
    }
}
