<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{RegistrationRequest, ResendCodeRequest, ResetPasswordRequest};
use Illuminate\Auth\Access\AuthorizationException;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $authRepository;

    /**
     * Create a new event instance.
     *
     * @param  \App\Repositories\Contracts\AuthRepositoryInterface  $authRepository
     * @return void
     */
    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * Register any application services.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function login(Request $request)
    {
        try {
            $data = $this->authRepository->logInUser($request);

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
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(RegistrationRequest $request) {
        $data = $this->authRepository->registerUser($request);

        return response()->json($data, 201);
    }

    /**
     * Verify a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function verify(Request $request)
    {
        try {
            $data = $this->authRepository->verifyUser($request);

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
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resendVerificationCode(ResendCodeRequest $request)
    {
        try {
            $data = $this->authRepository->resendCode($request);

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
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function requestPasswordReset(Request $request)
    {
        try {
            $data = $this->authRepository->sendPasswordResetLink($request);

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
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $data = $this->authRepository->resetPassword($request);

            return response()->json($data);
        }
        catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getCode());
        }
    }
}
