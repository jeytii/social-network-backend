<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\CreateOrUpdateLongTextRequest;
use App\Repositories\PostRepository;
use App\Services\PostService;

class PostController extends Controller
{
    protected $postRepository;

    protected $postService;

    /**
     * Create a new notification instance.
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
    public function get(Request $request)
    {
        $data = $this->postRepository->get($request);

        return response()->json($data);
    }

    /**
     * Create a new post.
     * 
     * @param \App\Http\Requests\CreateOrUpdateLongTextRequest  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(CreateOrUpdateLongTextRequest $request)
    {
        $data = $this->postService->createPost($request);

        return response()->json($data, 201);
    }

    /**
     * Update an existing post.
     * 
     * @param \App\Http\Requests\CreateOrUpdateLongTextRequest  $request
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(CreateOrUpdateLongTextRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $data = $this->postService->updatePost($request, $post);

        return response()->json($data);
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

        $data = $this->postService->deletePost($request->user(), $post->id);

        return response()->json($data);
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

        $data = $this->postService->likePost($request->user(), $post);

        return response()->json($data);
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

        $data = $this->postService->dislikePost($request->user(), $post->id);

        return response()->json($data);
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

        $data = $this->postService->bookmarkPost($request->user(), $post->id);

        return response()->json($data);
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

        $data = $this->postService->unbookmarkPost($request->user(), $post->id);

        return response()->json($data);
    }
}
