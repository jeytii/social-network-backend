<?php

namespace App\Http\Controllers;

use App\Http\Requests\{UpdateSettingRequest, VerifyUserRequest};
use App\Services\SettingService;

class SettingController extends Controller
{
    protected $settings;

    /**
     * Create a new controller instance.
     *
     * @param \App\Service\SettingService  $settings
     * @return void
     */
    public function __construct(SettingService $settings)
    {
        $this->settings = $settings;
    }
    
    /**
     * Make a request to update username
     * 
     * @param \App\Http\Requests\UpdateSettingRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestUsernameUpdate(UpdateSettingRequest $request)
    {
        $data = $this->settings->requestUsernameUpdate($request);

        return response()->json($data, $data['status']);
    }

    /**
     * Update the user's username.
     * 
     * @param \App\Http\Requests\VerifyUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUsername(VerifyUserRequest $request)
    {
        $data = $this->settings->updateUsername($request);

        return response()->json($data, $data['status']);
    }
}
