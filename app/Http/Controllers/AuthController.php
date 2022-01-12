<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{AuthRequest, UserRequest};
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @param \App\Services\AuthService  $auth
     * @return void
     */
    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Log in a user.
     *
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(AuthRequest $request)
    {
        $response = $this->auth->login($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Register a user.
     * 
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(UserRequest $request)
    {
        $response = $this->auth->register($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Verify a user.
     * 
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(AuthRequest $request)
    {
        $response = $this->auth->verify($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Resend another verification code to the user.
     * 
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationCode(AuthRequest $request)
    {
        $response = $this->auth->resendVerificationCode($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Send a password-reset request link to the user.
     * 
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestPasswordReset(AuthRequest $request)
    {
        $response = $this->auth->sendPasswordResetLink($request);

        return response()->json($response, $response['status']);
    }

     /**
     * Reset user's password.
     * 
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(AuthRequest $request)
    {
        $response = $this->auth->resetPassword($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Log out a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $response = $this->auth->logout($request);

        return response()->json($response, $response['status']);
    }
}
