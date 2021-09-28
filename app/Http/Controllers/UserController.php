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
     * Create a new notification instance.
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
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
        $data = $this->userRepository->get($request->query('query'));

        return response()->json($data);
    }

    /**
     * Get 3 randomly suggested users that the user is not yet following.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getRandom(Request $request)
    {
        $followingIds = $request->user()->following()->pluck('id')->toArray();

        $data = $this->userRepository->getRandom($followingIds);

        return response()->json($data);
    }

    /**
     * Search user(s) by name or username.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $data = $this->userRepository->search($request->query('query'));

        return response()->json($data);
    }

    /**
     * Follow a user.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function follow(Request $request, User $user)
    {
        $this->authorize('follow', $user);
        
        $data = $this->userService->follow($request->user(), $user);

        return response()->json($data);
    }

    /**
     * Unfollow a user.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unfollow(Request $request, User $user)
    {
        $this->authorize('unfollow', $user);

        $data = $this->userService->unfollow($request->user(), $user);

        return response()->json($data);
    }
}
