<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Repositories\ProfileRepository;
use Carbon\Carbon;
use Cloudinary\Cloudinary;

class ProfileController extends Controller
{
    protected $profile;

    protected $cloudinary;

    /**
     * Create a new notification instance.
     *
     * @param \App\Repositories\ProfileRepository  $profile
     * @return void
     */
    public function __construct(ProfileRepository $profile)
    {
        $this->profile = $profile;
        $this->cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
    }

    /**
     * Get the user's profile info.
     *
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(User $user)
    {
        $data = $user->loadCount(['followers', 'following', 'posts', 'comments'])
                    ->makeVisible('birth_date');

        return response()->json(compact('data'));
    }

    /**
     * Get user's own posts.
     *
     * @param \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPosts(User $user)
    {
        $response = $this->profile->get($user, 'posts');

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
        $response = $this->profile->get($user, 'comments');

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
        $response = $this->profile->getPostsOrComments($request, 'likedPosts');

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
        $response = $this->profile->getPostsOrComments($request, 'likedComments');

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
        $response = $this->profile->getPostsOrComments($request, 'bookmarks');

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
        $response = $this->profile->getUserConnections($user, 'followers');

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
        $response = $this->profile->getUserConnections($user, 'following');

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
        $image = $this->cloudinary->uploadApi()->upload(
            $request->file('image')->getRealPath(),
            [
                'folder' => 'social',
                'eager' => [
                    'width' => 200,
                    'height' => 200,
                    'crop' => 'fill',
                    'aspect_ratio' => 1.0,
                    'radius' => 'max',
                ]
            ]
        );

        return response()->json([
            'data' => $image['public_id'],
        ]);
    }

    /**
     * Update auth user's profile.
     *
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserRequest $request)
    {   
        if (empty($request->input('image_url')) && !is_null($request->user()->image_url)) {
            $this->cloudinary->uploadApi()->destroy($request->user()->image_url);
        }

        $body = $request->only(['name', 'bio', 'image_url']);
        $birthDate = Carbon::parse($request->input('birth_date'));

        $request->user()->update(array_merge($body, [
            'birth_date' => $birthDate
        ]));

        return response()->success();
    }
}
