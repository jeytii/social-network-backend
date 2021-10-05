<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\PostAndCommentRequest;
use App\Repositories\PostRepository;
use App\Services\PostService;

class PostController extends Controller
{
    protected $postRepository;

    protected $postService;

    /**
     * Create a new controller instance.
     *
     * @param \App\Repositories\PostRepository  $postRepository
     * @param \App\Services\PostService  $postService
     * @return void
     */
    public function __construct(PostRepository $postRepository, PostService $postService)
    {
        $this->postRepository = $postRepository;
        $this->postService = $postService;
    }

    /**
     * Get paginated news feed posts.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->postRepository->get($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Create a new post.
     * 
     * @param \App\Http\Requests\PostAndCommentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PostAndCommentRequest $request)
    {
        $response = $this->postService->createPost($request);

        return response()->json($response, $response['status']);
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

        $response = $this->postService->updatePost($request, $post);

        return response()->json($response, $response['status']);
    }

    /**
     * Delete an existing post.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, Post $post)
    {
        $this->authorize('delete', $post);

        $response = $this->postService->deletePost($request->user(), $post->id);

        return response()->json($response, $response['status']);
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

        $response = $this->postService->likePost($request->user(), $post);

        return response()->json($response, $response['status']);
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

        $response = $this->postService->dislikePost($request->user(), $post->id);

        return response()->json($response, $response['status']);
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

        $response = $this->postService->bookmarkPost($request->user(), $post->id);

        return response()->json($response, $response['status']);
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

        $response = $this->postService->unbookmarkPost($request->user(), $post->id);

        return response()->json($response, $response['status']);
    }
}
