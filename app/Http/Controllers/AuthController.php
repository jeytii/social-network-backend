<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{RegistrationRequest, ResendCodeRequest, ResetPasswordRequest};
use App\Services\AuthService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
{
    // FIXME: Clean up error handling and fix the doc blocks

    /**
     * Register any application services.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Services\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function login(Request $request, AuthService $authService)
    {
        try {
            $data = $authService->login($request);

            return response()->json($data);
        }
        catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'data' => [
                    'resend_code_url' => config('app.url') . '/api/verify/resend',
                    'username' => $request->username,
                ],
            ], $exception->getCode());
        }
        catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getCode());    
        }
    }

    /**
     * Register a user.
     * 
     * @param \App\Http\Requests\RegistrationRequest  $request
     * @param \App\Services\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(RegistrationRequest $request, AuthService $authService) {
        $data = $authService->register($request);

        return response()->json($data, 201);
    }

    /**
     * Verify a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Services\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function verify(Request $request, AuthService $authService)
    {
        try {
            $data = $authService->verify($request);

            return response()->json($data);
        }
        catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getCode());
        }
    }

    /**
     * Resend another verification code to the user.
     * 
     * @param \App\Http\Requests\ResendCodeRequest  $request
     * @param \App\Services\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resendVerificationCode(ResendCodeRequest $request, AuthService $authService)
    {
        try {
            $data = $authService->resendVerificationCode($request);

            return response()->json($data);
        }
        catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getCode());
        }
    }

    /**
     * Send a password-reset request link to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Services\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function requestPasswordReset(Request $request, AuthService $authService)
    {
        try {
            $data = $authService->sendPasswordResetLink($request);

            return response()->json($data);
        }
        catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }
        catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getCode());
        }
    }

     /**
     * Reset user's password.
     * 
     * @param \App\Http\Requests\ResetPasswordRequest  $request
     * @param \App\Services\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resetPassword(ResetPasswordRequest $request, AuthService $authService)
    {
        try {
            $data = $authService->resetPassword($request);

            return response()->json($data);
        }
        catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getCode());
        }
    }
}
