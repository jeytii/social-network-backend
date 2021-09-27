<?php

namespace App\Http\Controllers;

use App\Models\{User, Post, Comment};
use Illuminate\Http\Request;
use App\Http\Requests\CreateOrUpdateLongTextRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifyUponAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentController extends Controller
{
    /**
     * Store a new comment.
     * 
     * @return \Illuminate\Http\Response  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function get(Request $request)
    {
        try {
            $post = Post::where('slug', $request->query('pid'))->firstOrFail();
            $data = $post->comments()->withUser()->orderByDesc('created_at')->withPaginated();

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            abort(404, $exception->getMessage());
        }
    }

    /**
     * Store a new comment.
     * 
     * @param \App\Http\Requests\CreateOrUpdateLongTextRequest  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function store(CreateOrUpdateLongTextRequest $request)
    {
        try {
            $post = Post::where('slug', $request->query('uid'))->firstOrFail();
            $mentions = Str::of($request->body)->matchAll('/@[a-zA-Z0-9_]+/');
            $usernames = $mentions->map(fn($mention) => Str::replace('@', '', $mention))->toArray();
            $mentionedUsers = User::whereIn('username', $usernames)->get();
        
            $comment = $request->user()
                        ->comments()
                        ->create([
                            'post_id' => $post->id,
                            'body' => $request->body,
                        ])
                        ->withUser()
                        ->first();

            if (!$mentionedUsers->contains('username', $post->user->username)) {
                $post->user->notify(new NotifyUponAction(
                    $request->user(),
                    config('api.notifications.commented_on_post')
                ));
            }
            
            Notification::send($mentionedUsers, new NotifyUponAction(
                $request->user(),
                config('api.notifications.mentioned_on_comment')
            ));

            return response()->json(
                ['data' => compact('comment')],
                201
            );
        }
        catch (ModelNotFoundException $exception) {
            abort(404, $exception->getMessage());
        }
    }

    /**
     * Update an existing comment.
     * 
     * @param \App\Http\Requests\CreateOrUpdateLongTextRequest  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(CreateOrUpdateLongTextRequest $request, Comment $comment)
    {
        $this->authorize('update', $comment);
        
        $request->user()
            ->comments()
            ->find($comment->id)
            ->update($request->only('body'));

        return response()->json([
            'updated' => true,
            'message' => 'Comment successfully updated.'
        ]);
    }

    /**
     * Update an existing comment.
     * 
     * @return \Illuminate\Http\Request  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, Comment $comment)
    {
        $this->authorize('delete', $comment);
        
        $request->user()->comments()->find($comment->id)->delete();

        return response()->json([
            'deleted' => true,
            'message' => 'Comment successfully deleted.'
        ]);
    }

    /**
     * Get more comments from the user under a specific post.
     * 
     * @return \Illuminate\Http\Response  $request
     * @return \Illuminate\Http\Response
     */
    public function getMoreOwnComments(Request $request)
    {
        $data = $request->user()
                    ->comments()
                    ->whereHas('post', fn($q) => (
                        $q->where('slug', $request->query('pid'))
                    ))
                    ->orderByDesc('created_at')
                    ->withUser()
                    ->withPaginated(5);

            return response()->json($data);
    }
}
