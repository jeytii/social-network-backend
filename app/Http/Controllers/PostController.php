<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrUpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\Request;

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
        $ids = auth()->user()->following()->pluck('id')->merge(auth()->id());

        $data = Post::whereHas('user', fn($q) => $q->whereIn('id', $ids))
                    ->with('user:id,slug,name,username,gender,image_url')
                    ->withCount(['likers as likes_count', 'comments'])
                    ->when(!$sortByLikes, fn($q) => $q->orderByDesc('created_at'))
                    ->when($sortByLikes, fn($q) => $q->orderByDesc('likes_count'))
                    ->paginate(20);

        $hasMore = $data->hasMorePages();
        $nextOffset = $hasMore ? $data->currentPage() + 1 : null;

        return response()->json([
            'data' => $data->items(),
            'has_more' => $hasMore,
            'next_offset' => $nextOffset,
        ]);
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
        $data = auth()->user()->posts()
                    ->create($request->only('body'))
                    ->with('user:id,slug,name,username,gender,image_url')
                    ->first();

        $post = collect($data)->merge([
            'likes_count' => 0,
            'comments_count' => 0,
        ]);

        return response()->json(
            ['data' => compact('post')],
            201
        );
    }

    public function update(CreateOrUpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);
        
        auth()->user()->posts()
            ->find($post->id)
            ->update($request->only('body'));

        return response()->json([
            'updated' => true,
            'message' => 'Post successfully updated.'
        ]);
    }
}
