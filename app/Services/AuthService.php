<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SendVerificationCode;

class AuthService
{
    /**
     * Give access to a user.
     * 
     * @param \App\Models\User  $user
     * @param string  $message
     * @return array
     */
    public function authenticate(User $user, string $message): array
    {
        $token = $user->createToken($user->username)->plainTextToken;

        return compact('token', 'message');
    }

    /**
     * Generate a verification code for newly created user.
     * 
     * @param \App\Models\User  $user
     * @return void
     */
    public function sendVerificationCode(User $user)
    {
        $code = (string) random_int(100000, 999999);
        $token = bin2hex(random_bytes(16));

        cache(
            ["verification.{$token}" => [
                'id' => $user->id,
                'code' => $code,
            ]],
            60 * config('validation.expiration.verification')
        );

        $user->notify(new SendVerificationCode($code, $token));
    }
}
