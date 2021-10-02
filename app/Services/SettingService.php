<?php

namespace App\Services;

use App\Http\Requests\{UpdateSettingRequest, VerifyUserRequest};
use Illuminate\Support\Facades\DB;
use App\Notifications\SendVerificationCode;
use Exception;

class SettingService
{
    /**
     * Make a request to update username
     * 
     * @param \App\Http\Requests\UpdateSettingRequest  $request
     * @return array
     */
    public function requestUsernameUpdate(UpdateSettingRequest $request): array
    {
        try {
            DB::transaction(function() use ($request) {
                $code = random_int(100000, 999999);
    
                DB::table('username_updates')->updateOrInsert(
                    [
                        'user_id' => auth()->id(),
                        'completed_at' => null,
                    ],
                    [
                        'data' => $request->username,
                        'code' => $code,
                        'prefers_sms' => $request->prefers_sms,
                        'expiration' => now()->addMinutes(30),
                    ]
                );

                $request->user()->notify(new SendVerificationCode($code, $request->prefers_sms));
            });
    
            return [
                'status' => 200,
                'message' => 'Successfully request for username update.',
            ];
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.'
            ];
        }
    }

    /**
     * Update the user's username.
     * 
     * @param \App\Http\Requests\VerifyUserRequest  $request
     * @return array
     */
    public function updateUsername(VerifyUserRequest $request): array
    {
        if (DB::table('username_updates')->whereMonth('completed_at', date('m'))->count() === 3) {
            return [
                'status' => 401,
                'message' => 'You can only update your username 3 times in a month.'
            ];
        }

        $update = DB::table('username_updates')
                    ->where('code', $request->code)
                    ->where('expiration', '>', now());

        if ($update->doesntExist()) {
            return [
                'status' => 410,
                'message' => 'Verification code expired.'
            ];
        }

        $request->user()->update(['username' => $update->first()->data]);

        return [
            'status' => 200,
            'message' => 'Successfully updated the username.',
        ];
    }
}