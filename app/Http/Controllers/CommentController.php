<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateOrUpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
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
        $post = Post::where('slug', $request->query('user'));

        abort_if(!$post->exists(), 404, 'Post not found.');
        
        $comment = Comment::create([
                        'user_id' => auth()->id(),
                        'post_id' => $post->first()->id,
                        'body' => $request->body,
                    ])
                    ->with('user:id,slug,name,username,gender,image_url')
                    ->first();

        return response()->json(
            ['data' => compact('comment')],
            201
        );
    }
}
