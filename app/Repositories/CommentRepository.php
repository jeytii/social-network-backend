<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CommentRepository
{
    /**
     * Get comments under a specific post.
     * 
     * @param string  $postId
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function get(Request $request): array
    {
        try {
            $post = Post::where('slug', $request->query('pid'))->firstOrFail();
            $sortBy = $request->query('by', 'created_at');
            $type = $sortBy === 'likes' ? 'likes_count' : 'created_at';

            $data = $post->comments()->orderByDesc($type)->withPaginated();

            return array_merge($data, [
                'status' => 200,
                'message' => 'Successfully retrieved the comments.',
            ]);
        }
        catch (ModelNotFoundException $exception) {
            return [
                'status' => 404,
                'message' => $exception->getMessage(),
            ];
        }
    }
}