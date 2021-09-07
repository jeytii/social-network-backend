<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\CreateOrUpdatePostRequest;

class PostController extends Controller
{
    /**
     * Get paginated news feed posts.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
        $sortByLikes = $request->query('sort') === 'likes';
        $ids = $request->user()->following()->pluck('id')->merge(auth()->id());

        $data = Post::whereHas('user', fn($q) => $q->whereIn('id', $ids))
                    ->withUser()
                    ->withCount(['likers as likes_count', 'comments'])
                    ->when(!$sortByLikes, fn($q) => $q->orderByDesc('created_at'))
                    ->when($sortByLikes, fn($q) => $q->orderByDesc('likes_count'))
                    ->withPaginated();

        return response()->json($data);
    }

    /**
     * Store a new post.
     * 
     * @param \App\Http\Requests\CreateOrUpdatePostRequest  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(CreateOrUpdatePostRequest $request)
    {
        $post = $request->user()->posts()
                    ->create($request->only('body'))
                    ->withUser()
                    ->withCount(['likers as likes_count', 'comments'])
                    ->first();

        return response()->json(
            ['data' => compact('post')],
            201
        );
    }

    /**
     * Update an existing post.
     * 
     * @param \App\Http\Requests\CreateOrUpdatePostRequest  $request
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(CreateOrUpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);
        
        $request->user()->posts()
            ->find($post->id)
            ->update($request->only('body'));

        return response()->json([
            'updated' => true,
            'message' => 'Post successfully updated.'
        ]);
    }

    /**
     * Delete an existing post.
     * 
     * @param \App\Models\Post  $post
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, Post $post)
    {
        $this->authorize('delete', $post);

        $request->user()->posts()->find($post->id)->delete();

        return response()->json([
            'deleted' => true,
            'message' => 'Post successfully deleted.'
        ]);
    }

    /**
     * Add the post to the list of likes.
     * 
     * @param \App\Models\Post  $post
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function like(Request $request, Post $post)
    {
        $this->authorize('like', $post);

        $request->user()->likes()->attach($post->id);

        return response()->json([
            'liked' => true,
            'message' => 'Post successfully liked.'
        ]);
    }

    /**
     * Remove the post from the list of likes.
     * 
     * @param \App\Models\Post  $post
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function dislike(Request $request, Post $post)
    {
        $this->authorize('dislike', $post);

        $request->user()->likes()->detach($post->id);

        return response()->json([
            'disliked' => true,
            'message' => 'Post successfully disliked.'
        ]);
    }

    /**
     * Add the post to the list of bookmarks.
     * 
     * @param \App\Models\Post  $post
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function bookmark(Request $request, Post $post)
    {
        $this->authorize('bookmark', $post);

        $request->user()->bookmarks()->attach($post->id);

        return response()->json([
            'bookmarked' => true,
            'message' => 'Post successfully bookmarked.'
        ]);
    }

    /**
     * Remove the post from the list of bookmarks.
     * 
     * @param \App\Models\Post  $post
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unbookmark(Request $request, Post $post)
    {
        $this->authorize('unbookmark', $post);

        $request->user()->bookmarks()->detach($post->id);

        return response()->json([
            'unbookmarked' => true,
            'message' => 'Post successfully unbookmarked.'
        ]);
    }
}
