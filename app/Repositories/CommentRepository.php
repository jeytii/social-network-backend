<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentRepository
{
    /**
     * Get comments under a specific post.
     * 
     * @param string  $postId
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function get(string $postId): array
    {
        try {
            $post = Post::where('slug', $postId)->firstOrFail();

            $data = $post->comments()
                        ->withUser()
                        ->orderByDesc('created_at')
                        ->withPaginated();

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