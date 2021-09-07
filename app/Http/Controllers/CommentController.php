<?php

namespace App\Http\Controllers;

use App\Models\{Post, Comment};
use Illuminate\Http\Request;
use App\Http\Requests\CreateOrUpdateCommentRequest;
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
     * @param \App\Http\Requests\CreateOrUpdateCommentRequest  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function store(CreateOrUpdateCommentRequest $request)
    {
        $post = Post::where('slug', $request->query('uid'));

        abort_if(!$post->exists(), 404, 'Post not found.');
        
        $comment = $request->user()
                    ->comments()
                    ->create([
                        'post_id' => $post->first()->id,
                        'body' => $request->body,
                    ])
                    ->withUser()
                    ->first();

        return response()->json(
            ['data' => compact('comment')],
            201
        );
    }

    /**
     * Update an existing comment.
     * 
     * @param \App\Http\Requests\CreateOrUpdateCommentRequest  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(CreateOrUpdateCommentRequest $request, Comment $comment)
    {
        $this->authorize('update', $comment);
        
        $request->user()->comments()
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
}
