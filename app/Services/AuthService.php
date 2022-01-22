<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use App\Notifications\{ResetPassword, SendVerificationCode};
use Exception;

class AuthService
{
    /**
     * Give access to a user.
     * 
     * @param \App\Models\User  $user
     * @param string  $message
     * @return array
     */
    private function authenticateUser(User $user, string $message): array
    {
        $token = $user->createToken($user->username)->plainTextToken;
        $status = 200;

        return compact('token', 'message', 'status');
    }

    /**
     * Generate a verification code for newly created user.
     * 
     * @param \App\Models\User  $user
     * @return void
     */
    private function sendVerificationCode(User $user)
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

    /**
     * Log in a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function login(Request $request): array
    {
        $user = User::whereUsername($request->input('username'));

        if (
            !$user->exists() ||
            !Hash::check($request->input('password'), $user->first()->password)
        ) {
            return [
                'status' => 404,
                'message' => 'Incorrect combination.',
            ];
        }

        if (!$user->first()->hasVerifiedEmail()) {
            return [
                'status' => 401,
                'message' => 'Your account is not yet verified.',
                'data' => $request->only('username'),
            ];
        }

        return $this->authenticateUser($user->first(), 'Login successful');
    }

    /**
     * Register a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function register(Request $request): array
    {
        $body = $request->except('password', 'password_confirmation');
        $password = Hash::make($request->input('password'));
        
        try {
            DB::transaction(function() use ($request, $body, $password) {
                $user = User::create(array_merge($body, compact('password')));
                $this->sendVerificationCode($user);
            });
    
            return [
                'status' => 201,
            ];
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
    }

    /**
     * Verify a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function verify(Request $request): array
    {
        $cacheKey = "verification.{$request->input('token')}";
        $verification = cache($cacheKey);

        if (!$verification || $request->input('code') !== $verification['code']) {
            return [
                'status' => 401,
                'message' => 'Invalid verification code.',
            ];
        }

        $user = User::find($verification['id']);

        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 409,
                'message' => 'You have already verified your account.',
            ];
        }
        
        try {
            $response = DB::transaction(function() use ($user) {
                $user->markEmailAsVerified();

                return $this->authenticateUser($user, 'You have successfully verified your account.');
            });

            cache()->forget($cacheKey);

            return $response;
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
    }

    /**
     * Resend another verification code to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function resendVerificationCode(Request $request): array
    {
        $user = User::whereUsername($request->input('username'))->first();
        
        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 409,
                'message' => 'You have already verified your account.',
            ];
        }
        
        $this->sendVerificationCode($user);

        return [
            'status' => 200,
        ];
    }

    /**
     * Send a password-reset request link to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Exception
     */
    public function sendPasswordResetLink(Request $request): array
    {
        $emailAddress = $request->input('email');
        $user = User::firstWhere('email', $emailAddress);
        $interval = config('validation.attempts.change_password.interval');
        $rateLimitReached = $user->rateLimitReached(
            DB::table('password_resets')->where('email', $emailAddress)->whereNotNull('completed_at'),
            config('validation.attempts.change_password.max'),
            $interval,
            'completed_at'
        );
        
        if ($rateLimitReached) {
            return [
                'status' => 429,
                'message' => "You're doing too much. Try again in {$interval} hours.",
            ];
        }

        $token = bin2hex(random_bytes(16));

        try {
            DB::transaction(function() use ($user, $token) {
                DB::table('password_resets')->updateOrInsert(
                    [
                        'email' => $user->email,
                        'completed_at' => null
                    ],
                    [
                        'token' => $token
                    ]
                );

                $user->notify(new ResetPassword(config('app.frontend_url') . "/reset-password/{$token}"));
            });

            cache(
                ["password-reset.{$token}" => $emailAddress],
                60 * config('validation.expiration.password_reset')
            );
    
            return [
                'status' => 200,
                'message' => "Please check for the link that has been sent to your email address.",
            ];
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
    }

    /**
     * Reset user's password.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Exception
     */
    public function resetPassword(Request $request): array
    {
        $token = $request->input('token');
        $query = DB::table('password_resets')->where('token', $token);
        $user = User::firstWhere('email', $query->first()->email);

        try {
            $response = DB::transaction(function() use ($request, $user, $query) {
                $user->update(['password' => Hash::make($request->input('password'))]);

                $query->update(['completed_at' => now()]);

                return $this->authenticateUser($user, 'You have successfully reset your password.');
            });

            cache()->forget("password-reset.{$token}");
    
            return $response;
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
    }

    /**
     * Log out a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function logout(Request $request): array
    {
        $request->user()->tokens()->delete();

        return [
            'status' => 200,
            'message' => 'Logout successful.',
        ];
    }
}
