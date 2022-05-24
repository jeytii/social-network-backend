<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $rateLimitReached = $request->user()->settingsUpdateLimitReached($column, $maxAttempts, $interval);

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
}