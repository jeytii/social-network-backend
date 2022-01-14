<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
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

        if ($column === 'username') {
            $maxAttempts = config('validation.attempts.change_username.max');
            $interval = config('validation.attempts.change_username.interval');
        }

        $rateLimitReached = $request->user()->rateLimitReached(
            DB::table('settings_updates')->where('user_id', auth()->id())->where('type', $column),
            $maxAttempts,
            $interval
        );

        if ($rateLimitReached) {
            return [
                'status' => 429,
                'message' => "You're doing too much. Try again in {$interval} hours.",
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
        $interval = config('validation.attempts.change_password.interval');
        $rateLimitReached = $user->rateLimitReached(
            DB::table('password_resets')->where('email', $user->email)->whereNotNull('completed_at'),
            config('validation.attempts.change_password.max'),
            $interval,
            'completed_at'
        );
        
        if ($rateLimitReached) {
            return [
                'status' => 429,
                'message' => "You're doing too much. Try again in {$interval} hours.",
            ];
        }

        try {
            DB::transaction(function() use ($user, $newPassword) {
                $user->update(['password' => Hash::make($newPassword)]);

                DB::table('password_resets')->insert([
                    'email' => $user->email,
                    'completed_at' => now(),
                ]);
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
     * Change the accent color.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function changeColor(Request $request)
    {
        $request->user()->update($request->only('color'));

        return ['status' => 200];
    }

    /**
     * Toggle dark mode.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function toggleDarkMode(Request $request)
    {
        $request->user()->update([
            'dark_mode' => $request->boolean('dark_mode')
        ]);

        return ['status' => 200];
    }
}