<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewController extends Controller
{
    /**
     * Check if a specific post exists.
     * 
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticatePost(Post $post)
    {
        return response()->json(['status' => 200]);
    }

    /**
     * Check if a specific verification token exists.
     * 
     * @param string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateVerificationToken(string $token)
    {
        $status = 200;
        $verification = DB::table('verifications')
                            ->where('token', $token)
                            ->where('expiration', '>', now());

        if ($verification->doesntExist()) {
            $status = 404;
        }

        return response()->json(compact('status'));
    }

    /**
     * Check if a specific reset password token exists.
     * 
     * @param string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateResetPasswordToken(string $token)
    {
        $status = 200;

        if (DB::table('password_resets')->where('token', $token)->doesntExist()) {
            $status = 404;
        }

        return response()->json(compact('status'));
    }
}
