<?php

namespace App\Services;

use App\Http\Requests\{UploadProfilePhotoRequest, UpdateUserRequest};
use Illuminate\Support\Str;
use Cloudinary\Cloudinary;

class ProfileService
{
    protected $cloudinary;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
    }

    /**
     * Upload an image as profile photo.
     * 
     * @param \App\Http\Requests\UploadProfilePhotoRequest  $request
     * @return array
     */
    public function uploadProfilePhoto(UploadProfilePhotoRequest $request): array
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
                ]
            ]
        );

        return [
            'status' => 200,
            'message' => 'Successfully uploaded an image.',
            'data' => $image['eager'][0]['secure_url'],
        ];
    }

    /**
     * Update user's profile.
     * 
     * @param \App\Http\Requests\UpdateUserReques  $request
     * @return array
     */
    public function update(UpdateUserRequest $request): array
    {
        $body = $request->user()->no_birthdate ?
                $request->validated() :
                $request->only(['name', 'location', 'bio', 'image_url']);

        if (empty($request->image_url) && !is_null($request->user()->image_url)) {
            $id = (string) Str::of($request->user()->image_url)->match('/social\/\w+/');
            $this->cloudinary->uploadApi()->destroy($id);
        }

        $request->user()->update($body);

        return [
            'status' => 200,
            'message' => 'Successfully updated the profile.',
        ];
    }
}