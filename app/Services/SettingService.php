<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Auth\Events\PasswordReset;
use Exception;

class SettingService
{
    /**
     * Update a column.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param string  $data
     * @return array
     * @throws \Exception
     */
    public function changeColumn(Request $request, string $column): array
    {
        $maxAttempts = config('validation.attempts.change_email_address.max');
        $interval = config('validation.attempts.change_email_address.interval');
        $query = DB::table('settings_updates')
                    ->where('user_id', auth()->id())
                    ->where('type', $column);

        if ($column === 'username') {
            $maxAttempts = config('validation.attempts.change_username.max');
            $interval = config('validation.attempts.change_username.interval');
        }

        if ($request->user()->rateLimitReached($query, $maxAttempts, $interval)) {
            return [
                'status' => 429,
                'message' => "You're doing too much. Try again later.",
            ];
        }

        try {
            DB::transaction(function() use ($request, $column) {
                DB::table('settings_updates')->insert([
                    'user_id' => auth()->id(),
                    'type' => $column,
                ]);

                if ($column === 'username') {
                    $request->user()->update([$column => $request->input($column)]);
                }
            });
    
            return ['status' => 200];
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
    }

    /**
     * Update password.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function changePassword(Request $request): array
    {
        $user = $request->user();
        $newPassword = $request->input('new_password');
        $maxAttempts = config('validation.attempts.change_password.max');
        $interval = config('validation.attempts.change_password.interval');
        $query = DB::table('password_resets')
                    ->where('email', $user->email)
                    ->whereNotNull('completed_at');
        
        if ($user->rateLimitReached($query, $maxAttempts, $interval, 'completed_at')) {
            return [
                'status' => 429,
                'message' => "You're doing too much. Try again later.",
            ];
        }

        try {
            DB::transaction(function() use ($user, $newPassword) {
                $user->update(['password' => Hash::make($newPassword)]);

                DB::table('password_resets')->updateOrInsert(
                    [
                        'email' => $user->email,
                        'completed_at' => null
                    ],
                    [
                        'completed_at' => now()
                    ]
                );
            });

            event(new PasswordReset($user));

            return ['status' => 200];
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
    }
}