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
     * @param string  $method
     * @param string  $token
     * @return void
     */
    private function sendVerificationCode(User $user, string $method, string $token)
    {
        $code = random_int(100000, 999999);

        DB::table('verifications')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'token' => $token,
                'code' => $code,
                'expiration' => now()->addMinutes(config('validation.expiration.verification')),
            ]
        );

        $user->notify(new SendVerificationCode($code, $token, $method));
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
        $body = $request->except('password', 'password_confirmation', 'method');
        $password = Hash::make($request->input('password'));
        $token = uniqid();
        
        try {
            DB::transaction(function() use ($request, $body, $password, $token) {
                $user = User::create(array_merge($body, compact('password')));
                $this->sendVerificationCode($user, $request->input('method'), $token);
            });
    
            return [
                'status' => 201,
                'url' => "/verify/{$token}",
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
        try {
            $verification = DB::table('verifications')
                                ->where('code', $request->input('code'))
                                ->where('expiration', '>', now());

            if ($verification->doesntExist()) {
                return [
                    'status' => 401,
                    'message' => 'Invalid verification code.',
                ];
            }

            $user = User::find($verification->first()->user_id);

            if ($user->hasVerifiedEmail()) {
                return [
                    'status' => 409,
                    'message' => 'You have already verified your account.',
                ];
            }
            
            $response = DB::transaction(function() use ($user) {
                $user->markEmailAsVerified();

                return $this->authenticateUser($user, 'You have successfully verified your account.');
            });

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
        
        $this->sendVerificationCode($user, $request->input('method'), uniqid());

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
        $query = DB::table('password_resets')->where('email', $emailAddress)->whereNotNull('completed_at');
        $maxAttempts = config('validation.attempts.change_password.max');
        $interval = config('validation.attempts.change_password.interval');
        
        if ($user->rateLimitReached($query, $maxAttempts, $interval, 'completed_at')) {
            return [
                'status' => 429,
                'message' => "You're doing too much. Try again later.",
            ];
        }

        try {
            $type = DB::transaction(function() use ($request, $user, $emailAddress) {
                $prefersSMS = $request->input('method') === 'sms';
                $token = uniqid();
        
                DB::table('password_resets')->updateOrInsert(
                    [
                        'email' => $emailAddress,
                        'completed_at' => null,
                    ],
                    [
                        'token' => $token,
                        'expiration' => now()->addMinutes(config('validation.expiration.password_reset')),
                    ]
                );
                
                $user->notify(new ResetPassword(
                    config('app.client_url') . "/reset-password/{$token}",
                    $prefersSMS
                ));
    
                return $prefersSMS ? 'phone number' : 'email address';
            });
    
            return [
                'status' => 200,
                'message' => "Please check for the link that has been sent to your {$type}.",
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
        try {
            $response = DB::transaction(function() use ($request) {
                $user = User::firstWhere('email', $request->input('email'));

                $user->update(['password' => Hash::make($request->input('password'))]);
    
                DB::table('password_resets')
                    ->where('token', $request->input('token'))
                    ->update(['completed_at' => now()]);

                return $this->authenticateUser($user->first(), 'You have successfully reset your password.');
            });
    
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
