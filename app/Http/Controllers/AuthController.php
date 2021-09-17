<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Http\Requests\{RegistrationRequest, ResetPasswordRequest};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $data = $this->authRepository->verifyUser($request);

        return response()->json($data);
    }

    /**
     * Resend another verification code to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resendVerificationCode(Request $request)
    {
        $data = $this->authRepository->resendCode($request);

        return response()->json($data);
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
        $data = $this->authRepository->sendPasswordResetLink($request);

        return response()->json($data);
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
        $data = $this->authRepository->resetPassword($request);

        return response()->json($data);
    }
}
