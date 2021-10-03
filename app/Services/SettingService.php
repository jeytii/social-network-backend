<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Notifications\SendVerificationCode;
use Exception;

class SettingService
{
    /**
     * Make a request to update username
     * 
     * @param string  $table
     * @param string  $data
     * @param bool  $prefersSMS
     * @return void
     * @throws \Exception
     */
    public function requestUpdate(string $table, string $data, bool $prefersSMS)
    {
        if (DB::table($table)->whereMonth('completed_at', date('m'))->count() === 3) {
            return [
                'status' => 401,
                'message' => 'You can only do an update 3 times in a month.'
            ];
        }

        try {
            DB::transaction(function() use ($table, $data, $prefersSMS) {
                $code = random_int(100000, 999999);
                $request = [
                    'data' => $data,
                    'code' => $code,
                    'expiration' => now()->addMinutes(30),
                ];
    
                if ($table === 'username_updates') {
                    $request = array_merge($request, [
                        'prefers_sms' => $prefersSMS
                    ]);
                }
    
                DB::table($table)->updateOrInsert(
                    [
                        'user_id' => auth()->id(),
                        'completed_at' => null,
                    ],
                    $request
                );
    
                auth()->user()->notify(new SendVerificationCode($code, $prefersSMS));
            });

            return [
                'status' => 200,
                'message' => 'Successfully made a request.',
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
     * Update a column.
     * 
     * @param string  $column
     * @param string  $table
     * @param int  $code
     * @return array
     */
    public function updateColumn(string $column, string $table, int $code)
    {
        $data = DB::table($table)
                    ->where('user_id', auth()->id())
                    ->where('code', $code)
                    ->first()->data;

        auth()->user()->update([$column => $data]);

        return [
            'status' => 200,
            'message' => 'Update successful.',
        ];
    }
}