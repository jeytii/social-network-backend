<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Requests\PostAndCommentRequest;
use App\Repositories\CommentRepository;
use App\Services\CommentService;

class CommentController extends Controller
{
    protected $commentRepository;

    protected $commentService;

    /**
     * Create a new controller instance.
     *
     * @param \App\Repository\CommentRepository  $commentRepository
     * @param \App\Service\CommentService  $commentService
     * @return void
     */
    public function __construct(CommentRepository $commentRepository, CommentService $commentService)
    {
        $this->commentRepository = $commentRepository;
        $this->commentService = $commentService;
    }

    /**
     * Get comments under a specific post.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $data = $this->commentRepository->get($request);

        return response()->json($data, $data['status']);
    }

    /**
     * Store a new comment.
     * 
     * @param \App\Http\Requests\PostAndCommentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PostAndCommentRequest $request)
    {
        $data = $this->commentService->createComment($request);

        return response()->json($data, 201);
    }

    /**
     * Update a comment.
     * 
     * @param \App\Http\Requests\PostAndCommentRequest  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(PostAndCommentRequest $request, Comment $comment)
    {
        $this->authorize('update', $comment);
        
        $data = $this->commentService->updateComment($request, $comment->id);

        return response()->json($data);
    }

    /**
     * Update an existing comment.
     * 
     * @return \Illuminate\Http\Request  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, Comment $comment)
    {
        $this->authorize('delete', $comment);

        $data = $this->commentService->deleteComment($request->user(), $comment->id);

        return response()->json($data);
    }

    /**
     * Add the comment to the list of likes.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function like(Request $request, Comment $comment)
    {
        $this->authorize('like', $comment);

        $response = $this->commentService->likeComment($request->user(), $comment);

        return response()->json($response, $response['status']);
    }

    /**
     * Remove the comment from the list of likes.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Comment  $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function dislike(Request $request, Comment $comment)
    {
        $this->authorize('dislike', $comment);

        $response = $this->commentService->dislikeComment($request->user(), $comment->id);

        return response()->json($response, $response['status']);
    }
}
