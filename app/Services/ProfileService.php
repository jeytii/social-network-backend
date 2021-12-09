<?php

namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;
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
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function uploadProfilePhoto(Request $request): array
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

        return [
            'status' => 200,
            'data' => $image['public_id'],
        ];
    }

    /**
     * Update user's profile.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function update(Request $request): array
    {
        if (empty($request->input('image_url')) && !is_null($request->user()->image_url)) {
            $this->cloudinary->uploadApi()->destroy($request->user()->image_url);
        }

        $body = $request->only(['name', 'bio', 'image_url']);
        $birthDate = Carbon::parse($request->input('birth_date'));

        $request->user()->update(array_merge($body, [
            'birth_date' => $birthDate
        ]));

        return ['status' => 200];
    }
}
