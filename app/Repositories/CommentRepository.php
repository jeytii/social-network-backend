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
            $post = Post::where('slug', $request->query('post'))->firstOrFail();
            $data = $post->comments()
                        ->orderByDesc('created_at')
                        ->orderByDesc('likes_count')
                        ->withPaginated();

            return array_merge($data, [
                'status' => 200,
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