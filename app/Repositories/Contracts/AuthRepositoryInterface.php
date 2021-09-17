<?php

namespace App\Repositories\Contracts;

use App\Http\Requests\{RegistrationRequest, ResetPasswordRequest};
use Illuminate\Http\Request;

interface AuthRepositoryInterface
{
    /**
     * Log in a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException 
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function logInUser(Request $request): array;

    /**
     * Register a user.
     * 
     * @param \App\Http\Requests\RegistrationRequest  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function registerUser(RegistrationRequest $request): array;

    /**
     * Verify a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function verifyUser(Request $request): array;

    /**
     * Resend another verification code to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resendCode(Request $request): array;

    /**
     * Send a password-reset request link to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function sendPasswordResetLink(Request $request): array;

     /**
     * Reset user's password.
     * 
     * @param \App\Http\Requests\ResetPasswordRequest  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resetPassword(ResetPasswordRequest $request): array;
}