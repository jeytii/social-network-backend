<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UpdateUserRequest;
use App\Repositories\ProfileRepository;
use App\Services\ProfileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProfileController extends Controller
{
    // FIXME: Clean up error handling and fix some doc blocks.

    protected $profileRepository;

    protected $profileService;

    /**
     * Create a new notification instance.
     *
     * @param \App\Repositories\ProfileRepository  $profileRepository
     * @param \App\Services\ProfileService  $profileService
     * @return void
     */
    public function __construct(ProfileRepository $profileRepository, ProfileService $profileService)
    {
        $this->profileRepository = $profileRepository;
        $this->profileService = $profileService;
    }

    /**
     * Get the user's profile info.
     *
     * @param string  $username
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getInfo(string $username)
    {
        try {
            $user = User::withCount('followers', 'following')
                        ->where('username', $username)
                        ->firstOrFail();
            $data = $this->profileRepository->get($user);

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }
    }

    /**
     * Get user's own posts.
     *
     * @param string  $username
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getPosts(string $username)
    {
        try {
            $user = User::withCount('followers', 'following')
                        ->where('username', $username)
                        ->firstOrFail();
            $data = $this->profileRepository->getPosts($user, 'posts');

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'User not found.'
            ], $exception->getCode());
        }
    }

    /**
     * Get user's own comments.
     *
     * @param string  $username
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getComments(string $username)
    {
        try {
            $user = User::withCount('followers', 'following')
                        ->where('username', $username)
                        ->firstOrFail();
            $data = $this->profileRepository->getComments($user->id);

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'User not found.'
            ], $exception->getCode());
        }
    }

    /**
     * Get posts liked by user.
     *
     * @param string  $username
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getLikes(string $username)
    {
        try {
            $user = User::withCount('followers', 'following')
                        ->where('username', $username)
                        ->firstOrFail();
            $data = $this->profileRepository->getPosts($user, 'likes');

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'User not found.'
            ], $exception->getCode());
        }
    }

    /**
     * Get posts bookmarked by user.
     *
     * @param string  $username
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getBookmarks(string $username)
    {
        try {
            $user = User::withCount('followers', 'following')
                        ->where('username', $username)
                        ->firstOrFail();
            $data = $this->profileRepository->getPosts($user, 'bookmarks');

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'User not found.'
            ], $exception->getCode());
        }
    }

    /**
     * Get user's followers.
     *
     * @param string  $username
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getFollowers(string $username)
    {
        try {
            $user = User::withCount('followers', 'following')
                        ->where('username', $username)
                        ->firstOrFail();
            $data = $this->profileRepository->getConnections($user, 'followers');

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'User not found.'
            ], $exception->getCode());
        }
    }

    /**
     * Get other users followed by user.
     *
     * @param string  $username
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getFollowedUsers(string $username)
    {
        try {
            $user = User::withCount('followers', 'following')
                        ->where('username', $username)
                        ->firstOrFail();
            $data = $this->profileRepository->getConnections($user, 'following');

            return response()->json($data);
        }
        catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'User not found.'
            ], $exception->getCode());
        }
    }

    /**
     * Update auth user's profile.
     *
     * @param \App\Http\Requests\UpdateUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request)
    {   
        $data = $this->profileService->update($request);

        return response()->json($data);
    }
}
