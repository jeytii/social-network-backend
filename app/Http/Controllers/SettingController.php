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
     * Make a request to update username.
     * 
     * @param \App\Http\Requests\UpdateSettingRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestUsernameUpdate(UpdateSettingRequest $request)
    {
        $response = $this->settings->requestUpdate('username_updates', $request->username, $request->prefers_sms);

        return response()->json($response, $response['status']);
    }

    /**
     * Update the user's username.
     * 
     * @param \App\Http\Requests\VerifyUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUsername(VerifyUserRequest $request)
    {
        $response = $this->settings->updateColumn('username', 'username_updates', $request->code);

        return response()->json($response);
    }

    /**
     * Make a request to update email address.
     * 
     * @param \App\Http\Requests\UpdateSettingRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestEmailAddressUpdate(UpdateSettingRequest $request)
    {
        $response = $this->settings->requestUpdate('email_address_updates', $request->email, false);

        return response()->json($response, $response['status']);
    }

    /**
     * Update the user's email address.
     * 
     * @param \App\Http\Requests\VerifyUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEmailAddress(VerifyUserRequest $request)
    {
        $response = $this->settings->updateColumn('email', 'email_address_updates', $request->code);

        return response()->json($response);
    }

    /**
     * Make a request to update phone number.
     * 
     * @param \App\Http\Requests\UpdateSettingRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestPhoneNumberUpdate(UpdateSettingRequest $request)
    {
        $response = $this->settings->requestUpdate('phone_number_updates', $request->phone_number, true);

        return response()->json($response, $response['status']);
    }

    /**
     * Update the user's phone number.
     * 
     * @param \App\Http\Requests\VerifyUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhoneNumber(VerifyUserRequest $request)
    {
        $response = $this->settings->updateColumn('phone_number', 'phone_number_updates', $request->code);

        return response()->json($response);
    }

    /**
     * Update the user's password.
     * 
     * @param \App\Http\Requests\UpdateSettingRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(UpdateSettingRequest $request)
    {
        $response = $this->settings->updatePassword($request->new_password);

        return response()->json($response);
    }
}
