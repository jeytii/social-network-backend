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
        $sortType = $request->query('sort') === 'likes' ? 'likes_count' : 'created_at';
        $ids = $request->user()->following()->pluck('id')->merge(auth()->id());

        $data = Post::whereHas('user', fn($q) => $q->whereIn('id', $ids))
                    ->orderByDesc($sortType)
                    ->withPaginated();

        return array_merge($data, [
            'status' => 200,
            'message' => 'Successfully retrieved posts.',
        ]);
    }
}