<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateUserRequest;

class ProfileService
{
    /**
     * Upload an image as profile photo.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function uploadProfilePhoto(Request $request): array
    {
        $image = $request->file('image')->storeOnCloudinary();

        return [
            'status' => 200,
            'message' => 'Successfully uploaded an image.',
            'data' => $image->getSecurePath(),
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

        $request->user()->update($body);

        return [
            'status' => 200,
            'message' => 'Successfully updated the profile.',
        ];
    }
}