<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\{DB, Hash};
use Exception;

class SettingService extends RateLimitService
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
        $query = DB::table('settings_updates')
                    ->where('user_id', auth()->id())
                    ->where('type', $column);

        if ($this->rateLimitReached($query, 3, 72)) {
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
        
                $request->user()->update([$column => $request->input($column)]);
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
     * @param string  $newPassword
     * @return array
     */
    public function changePassword(string $newPassword): array
    {
        $query = DB::table('password_resets')
                    ->where('email', auth()->user()->email)
                    ->whereNotNull('completed_at');
        
        if ($this->rateLimitReached($query, 7, 72, 'completed_at')) {
            return [
                'status' => 429,
                'message' => "You're doing too much. Try again later.",
            ];
        }

        try {
            DB::transaction(function() use ($newPassword) {
                $pr = DB::table('password_resets')
                        ->where('email', auth()->user()->email)
                        ->whereNull('completed_at');
                        
                auth()->user()->update([
                    'password' => Hash::make($newPassword)
                ]);
                
                if ($pr->exists()) {
                    $pr->update(['completed_at' => now()]);
                }
                else {
                    DB::table('password_resets')->insert([
                        'email' => auth()->user()->email,
                        'completed_at' => now(),
                    ]);
                }
        
                event(new PasswordReset(auth()->user()));
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
}