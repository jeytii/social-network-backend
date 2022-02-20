<?php

namespace App\Http\Controllers;

use App\Models\{Post, Comment, Notification};
use Illuminate\Http\Request;
use App\Http\Requests\CommentRequest;
use Illuminate\Support\Facades\DB;
use App\Notifications\NotifyUponAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class CommentController extends Controller
{
    /**
     * Get comments under a specific post.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $post = Post::where('slug', $request->query('post'))->firstOrFail();
            $data = $post->comments()->orderByDesc('likes_count')->withPaginated();

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            return response()->error($exception->getMessage(), 404);
        }
    }

    /**
     * Store a new comment.
     * 
     * @param \App\Http\Requests\CommentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CommentRequest $request)
    {
        $post = Post::firstWhere('slug', $request->input('post'));
        $user = $request->user();

        try {
            $data = DB::transaction(function() use ($request, $user, $post) {
                $comment = $user->comments()->create([
                    'post_id' => $post->id,
                    'body' => $request->input('body')
                ])->first();

                if ($post->user->isNot($user)) { 
                    $post->user->notify(new NotifyUponAction(
                        $user,
                        Notification::COMMENTED_ON_POST,
                        "/post/{$post->slug}"
                    ));
                }

                return $comment;
            });

            return response()->json(compact('data'), 201);
        }
        catch (Exception $exception) {
            return response()->somethingWrong();
        }
    }

    /**
     * Update a comment.
     * 
     * @param \App\Http\Requests\CommentRequest  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(CommentRequest $request, Comment $comment)
    {
        $this->authorize('update', $comment);
        
        if ($comment->body === $request->input('body')) {
            return response()->error('No change was made.', 401);
        }

        $comment->update($request->only('body'));

        return response()->success();
    }

    /**
     * Delete an existing comment.
     * 
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();
        
        return response()->success();
    }

    /**
     * Add the comment to the list of likes.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function like(Request $request, Comment $comment)
    {
        $this->authorize('like', $comment);

        $liker = $request->user();

        try {
            DB::transaction(function() use ($liker, $comment) {
                $liker->likedComments()->attach($comment);

                if ($comment->user->isNot(auth()->user())) {
                    $comment->user->notify(new NotifyUponAction(
                        $liker,
                        Notification::LIKED_COMMENT,
                        "/post/{$comment->post->slug}"
                    ));
                }
            });

            return response()->json([
                'data' => $comment->likers()->count(),
            ]);
        }
        catch (Exception $exception) {
            return response()->somethingWrong();
        }
    }

    /**
     * Remove the comment from the list of likes.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function dislike(Request $request, Comment $comment)
    {
        $this->authorize('dislike', $comment);

        $request->user()->likedComments()->detach($comment);

        return response()->json([
            'data' => $comment->likers()->count(),
        ]);
    }
}