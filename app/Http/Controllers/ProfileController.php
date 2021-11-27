<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Services\ProfileService;
use App\Repositories\ProfileRepository;

class ProfileController extends Controller
{
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
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(User $user)
    {
        $response = $this->profileRepository->get($user);

        return response()->json($response);
    }

    /**
     * Get user's own posts.
     *
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPosts(User $user)
    {
        $response = $this->profileRepository->getPostsOrComments($user, 'posts');

        return response()->json($response);
    }

    /**
     * Get user's own comments.
     *
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComments(User $user)
    {
        $response = $this->profileRepository->getPostsOrComments($user, 'comments');

        return response()->json($response);
    }

    /**
     * Get posts liked by user.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLikedPosts(Request $request)
    {
        $response = $this->profileRepository->getInteractedPosts($request, 'likedPosts');

        return response()->json($response);
    }

    /**
     * Get comments liked by user.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLikedComments(Request $request)
    {
        $response = $this->profileRepository->getInteractedPosts($request, 'likedComments');

        return response()->json($response);
    }

    /**
     * Get posts bookmarked by user.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBookmarks(Request $request)
    {
        $response = $this->profileRepository->getInteractedPosts($request, 'bookmarks');

        return response()->json($response);
    }

    /**
     * Get user's followers.
     *
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFollowers(User $user)
    {
        $response = $this->profileRepository->getConnections($user, 'followers');

        return response()->json($response);
    }

    /**
     * Get other users followed by user.
     *
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFollowedUsers(User $user)
    {
        $response = $this->profileRepository->getConnections($user, 'following');

        return response()->json($response);
    }

    /**
     * Upload a profile photo.
     *
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePhoto(UserRequest $request)
    {   
        $response = $this->profileService->uploadProfilePhoto($request);

        return response()->json($response);
    }

    /**
     * Update auth user's profile.
     *
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserRequest $request)
    {   
        $response = $this->profileService->update($request);

        return response()->json($response);
    }
}
