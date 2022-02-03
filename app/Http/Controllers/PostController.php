<?php

namespace App\Http\Controllers;

use App\Models\{Post, Notification};
use Illuminate\Http\Request;
use App\Http\Requests\PostAndCommentRequest;
use Illuminate\Support\Facades\DB;
use App\Notifications\NotifyUponAction;
use Exception;

class PostController extends Controller
{
    /**
     * Get paginated news feed posts.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $ids = $request->user()->following()->pluck('id')->merge(auth()->id());
        $data = Post::whereHas('user', fn($q) => $q->whereIn('id', $ids))->latest()->withPaginated();

        return response()->json($data);
    }

    /**
     * Get a specific post.
     * 
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Post $post)
    {
        return response()->json(compact('post'));
    }

    /**
     * Create a new post.
     * 
     * @param \App\Http\Requests\PostAndCommentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PostAndCommentRequest $request)
    {
        $data = $request->user()->posts()->create($request->only('body'))->first();
        
        return response()->json(compact('data'), 201);
    }

    /**
     * Update an existing post.
     * 
     * @param \App\Http\Requests\PostAndCommentRequest  $request
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(PostAndCommentRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        if ($post->body === $request->input('body')) {
            return response()->error('No changes made.', 401);
        }

        $post->update($request->only('body'));

        return response()->success();
    }

    /**
     * Delete an existing post.
     * 
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->success();
    }

    /**
     * Add the post to the list of likes.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function like(Request $request, Post $post)
    {
        $this->authorize('like', $post);

        $liker = $request->user();

        try {
            DB::transaction(function() use ($liker, $post) {
                $liker->likedPosts()->attach($post);
                
                if ($post->user->isNot(auth()->user())) {
                    $post->user->notify(new NotifyUponAction($liker, Notification::LIKED_POST, "/posts/{$post->slug}"));
                }
            });

            return response()->json([
                'data' => $post->likers()->count(),
            ]);
        }
        catch (Exception $exception) {
            return response()->somethingWrong();
        }
    }

    /**
     * Remove the post from the list of likes.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function dislike(Request $request, Post $post)
    {
        $this->authorize('dislike', $post);

        $request->user()->likedPosts()->detach($post);

        return response()->json([
            'data' => $post->likers()->count(),
        ]);
    }

    /**
     * Add the post to the list of bookmarks.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function bookmark(Request $request, Post $post)
    {
        $this->authorize('bookmark', $post);

        $request->user()->bookmarks()->attach($post);

        return response()->success();
    }

    /**
     * Remove the post from the list of bookmarks.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unbookmark(Request $request, Post $post)
    {
        $this->authorize('unbookmark', $post);

        $request->user()->bookmarks()->detach($post);

        return response()->success();
    }
}
