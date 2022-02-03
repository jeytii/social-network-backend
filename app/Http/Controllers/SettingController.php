<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use App\Services\SettingService;
use App\Http\Requests\UserRequest;
use Exception;

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
        $user = $request->user();
        $newPassword = $request->input('new_password');
        
        if ($user->passwordResetLimitReached()) {
            return response()->error("You're doing too much. Try again later.", 429);
        }

        try {
            DB::transaction(function() use ($user, $newPassword) {
                $user->update(['password' => Hash::make($newPassword)]);

                DB::table('password_resets')->insert([
                    'email' => $user->email,
                    'completed_at' => now(),
                ]);
            });

            return response()->success();
        }
        catch (Exception $exception) {
            return response()->somethingWrong();
        }
    }

    /**
     * Change the accent color.
     * 
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeColor(UserRequest $request)
    {
        $request->user()->update($request->only('color'));

        return response()->success();
    }

    /**
     * Toggle dark mode.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleDarkMode(Request $request)
    {
        $request->user()->update([
            'dark_mode' => $request->boolean('dark_mode')
        ]);

        return response()->success();
    }
}
