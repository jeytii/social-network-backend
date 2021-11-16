<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Http\Request;

class PostRepository
{
    /**
     * Get paginated news feed posts.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function get(Request $request): array
    {
        $sortBy = $request->query('sort', 'created_at');
        $sortType = $sortBy === 'likes' ? 'likes_count' : 'created_at';
        $ids = $request->user()->following()->pluck('id')->merge(auth()->id());

        $data = Post::whereHas('user', fn($q) => $q->whereIn('id', $ids))
                    ->orderByDesc($sortType)
                    ->withPaginated();

        return array_merge($data, [
            'status' => 200,
        ]);
    }

    /**
     * Get a specific post.
     * 
     * @param \App\Models\Post  $post
     * @return array
     */
    public function getOne(Post $post): array
    {
        return [
            'status' => 200,
            'post' => $post,
        ];
    }
}