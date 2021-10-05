<?php

namespace App\Http\Controllers;

use App\Http\Requests\{UploadProfilePhotoRequest, UpdateUserRequest};
use App\Repositories\ProfileRepository;
use App\Services\ProfileService;

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
     * @param string  $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(string $username)
    {
        $response = $this->profileRepository->get($username);

        return response()->json($response, $response['status']);
    }

    /**
     * Get user's own posts.
     *
     * @param string  $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPosts(string $username)
    {
        $response = $this->profileRepository->getPosts($username, 'posts');

        return response()->json($response, $response['status']);
    }

    /**
     * Get user's own comments.
     *
     * @param string  $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComments(string $username)
    {
        $response = $this->profileRepository->getComments($username);

        return response()->json($response, $response['status']);
    }

    /**
     * Get posts liked by user.
     *
     * @param string  $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLikes(string $username)
    {
        $response = $this->profileRepository->getPosts($username, 'likes');

        return response()->json($response, $response['status']);
    }

    /**
     * Get posts bookmarked by user.
     *
     * @param string  $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBookmarks(string $username)
    {
        $response = $this->profileRepository->getPosts($username, 'bookmarks');

        return response()->json($response, $response['status']);
    }

    /**
     * Get user's followers.
     *
     * @param string  $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFollowers(string $username)
    {
        $response = $this->profileRepository->getConnections($username, 'followers');

        return response()->json($response, $response['status']);
    }

    /**
     * Get other users followed by user.
     *
     * @param string  $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFollowedUsers(string $username)
    {
        $response = $this->profileRepository->getConnections($username, 'following');

        return response()->json($response, $response['status']);
    }

    /**
     * Upload a profile photo.
     *
     * @param \App\Http\Requests\UploadProfilePhotoRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePhoto(UploadProfilePhotoRequest $request)
    {   
        $response = $this->profileService->uploadProfilePhoto($request);

        return response()->json($response);
    }

    /**
     * Update auth user's profile.
     *
     * @param \App\Http\Requests\UpdateUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request)
    {   
        $response = $this->profileService->update($request);

        return response()->json($response);
    }
}
