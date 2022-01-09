<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SettingService;
use App\Http\Requests\UserRequest;

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
     * Update the user's username.
     * 
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeUsername(UserRequest $request)
    {
        $response = $this->settings->changeColumn($request, 'username');

        return response()->json($response, $response['status']);
    }

    /**
     * Update the user's email address.
     * 
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeEmailAddress(UserRequest $request)
    {
        $response = $this->settings->changeColumn($request, 'email');

        return response()->json($response, $response['status']);
    }

    /**
     * Update the user's password.
     * 
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(UserRequest $request)
    {
        $response = $this->settings->changePassword($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Change the accent color.
     * 
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeColor(UserRequest $request)
    {
        $response = $this->settings->changeColor($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Toggle dark mode.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleDarkMode(Request $request)
    {
        $response = $this->settings->toggleDarkMode($request);

        return response()->json($response, $response['status']);
    }
}
