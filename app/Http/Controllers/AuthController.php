<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{UserRequest, ResendCodeRequest, ResetPasswordRequest};
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
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $response = $this->auth->login($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Register a user.
     * 
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(UserRequest $request) {
        $response = $this->auth->register($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Verify a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'exists:verifications']
        ]);

        $response = $this->auth->verify($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Resend another verification code to the user.
     * 
     * @param \App\Http\Requests\ResendCodeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationCode(ResendCodeRequest $request)
    {
        $response = $this->auth->resendVerificationCode($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Send a password-reset request link to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestPasswordReset(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users']
        ]);

        $response = $this->auth->sendPasswordResetLink($request);

        return response()->json($response, $response['status']);
    }

     /**
     * Reset user's password.
     * 
     * @param \App\Http\Requests\ResetPasswordRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $response = $this->auth->resetPassword($request);

        return response()->json($response, $response['status']);
    }
}
