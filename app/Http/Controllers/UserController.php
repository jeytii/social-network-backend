<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\UserRepository;
use App\Services\UserService;

class UserController extends Controller
{
    protected $userRepository;

    protected $userService;

    /**
     * Create a new controller instance.
     *
     * @param \App\Repositories\UserRepository  $userRepository
     * @param \App\Services\UserService  $userService
     * @return void
     */
    public function __construct(UserRepository $userRepository, UserService $userService)
    {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    /**
     * Get paginated list of user models.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->userRepository->get($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Get the column values that will be used as route parameters on the client.
     * 
     * @param string  $column
     * @return \Illuminate\Http\JsonResponse
     */
    public function getParams(string $column)
    {
        $response = $this->userRepository->getParams($column);

        return response()->json($response, $response['status']);
    }

    /**
     * Get 3 randomly suggested users that the user is not yet following.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRandom(Request $request)
    {
        $response = $this->userRepository->getRandom($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Search user(s) by name or username.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $response = $this->userRepository->search($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Follow a user.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function follow(Request $request, User $user)
    {
        $this->authorize('follow', $user);
        
        $response = $this->userService->follow($request->user(), $user);

        return response()->json($response, $response['status']);
    }

    /**
     * Unfollow a user.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unfollow(Request $request, User $user)
    {
        $this->authorize('unfollow', $user);

        $response = $this->userService->unfollow($request->user(), $user);

        return response()->json($response, $response['status']);
    }
}
