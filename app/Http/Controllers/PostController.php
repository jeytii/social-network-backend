<?php

namespace App\Http\Controllers;

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
}
