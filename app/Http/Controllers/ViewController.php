<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ViewController extends Controller
{
    /**
     * Check user is logged in.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateUser(Request $request)
    {
        $data = $request->user()->only(array_merge(
            config('response.user'),
            ['email', 'bio', 'color', 'dark_mode']
        ));

        $data['birth_date'] = $request->user()->birth_date->format('Y-m-d');

        return response()->json(compact('data'));
    }

    /**
     * Check if a specific post exists.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticatePost()
    {
        return response()->success();
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
        $verification = cache("verification.{$token}");

        if (!$verification) {
            $status = 404;
        }

        return response()->json(compact('status'), $status);
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

        if (!cache("password-reset.{$token}")) {
            $status = 404;
        }

        return response()->json(compact('status'), $status);
    }
}
