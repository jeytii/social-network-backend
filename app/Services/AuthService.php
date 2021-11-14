<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Auth\Events\{Login, Registered};
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

        return [
            'status' => 200,
            'message' => $message,
            'token' => $token,
            'user' => $user->setHidden(['id']),
        ];
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
                'data' => [
                    'url' => route('auth.verify.resend'),
                    'username' => $request->input('username'),
                ],
            ];
        }

        event(new Login('api', $user->firstWithBasicOnly(), true));

        return $this->authenticateUser($user->firstWithBasicOnly(), 'Login successful');
    }

    /**
     * Register a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function register(Request $request): array
    {   
        try {
            $url = DB::transaction(function() use ($request) {
                $body = $request->only([
                    'name', 'email', 'username',
                    'phone_number', 'gender', 'birth_date'
                ]);
                $method = $request->input('method');
                $password = Hash::make($request->input('password'));
                $user = User::create(array_merge($body, compact('password')));
                $token = uniqid();
    
                event(new Registered($user));
                
                $this->sendVerificationCode($user, $method, $token);

                return "/verify/{$token}";
            });
    
            return [
                'status' => 201,
                'url' => $url,
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

                event(new Login('api', $user->firstWithBasicOnly(), true));

                return $this->authenticateUser(
                    $user->firstWithBasicOnly(),
                    'You have successfully verified your account.'
                );
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
                $token = uniqid();
                $prefersSMS = $request->input('method') === 'sms';
                $url = config('app.client_url') . "/reset-password/{$token}";
        
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
                
                $user->notify(new ResetPassword($url, $prefersSMS));
    
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
        
                event(new PasswordReset($user));

                event(new Login('api', $user->firstWithBasicOnly(), true));

                return $this->authenticateUser(
                    $user->firstWithBasicOnly(),
                    'You have successfully reset your password.'
                );
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
