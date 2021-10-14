<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Auth\Events\{Login, Registered};
use App\Notifications\{ResetPassword, SendVerificationCode};
use Exception;

class AuthService extends RateLimitService
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
     * @param bool  $prefersSMS
     * @return string
     */
    private function sendVerificationCode(User $user, bool $prefersSMS): string
    {
        $code = random_int(100000, 999999);

        DB::table('verifications')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'code' => $code,
                'prefers_sms' => $prefersSMS,
                'expiration' => now()->addMinutes(10),
            ]
        );

        $user->notify(new SendVerificationCode($code, $prefersSMS));

        return $prefersSMS ? 'phone number' : 'email address';
    }

    /**
     * Log in a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function login(Request $request): array
    {
        $user = User::whereUsername($request->username);

        if (
            !$user->exists() ||
            !Hash::check($request->password, $user->first()->password)
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
            $type = DB::transaction(function() use ($request) {
                $body = $request->only([
                    'name', 'email', 'username', 'phone_number',
                    'gender', 'birth_month', 'birth_day', 'birth_year'
                ]);
                $password = Hash::make($request->password);
                $user = User::create(array_merge($body, compact('password')));
    
                event(new Registered($user));
                
                return $this->sendVerificationCode($user, $request->prefers_sms);
            });
    
            return [
                'status' => 201,
                'message' => "Account successfully created. Please enter the verification code that was sent to your {$type}.",
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
        $verification = DB::table('verifications')
                            ->where('user_id', auth()->id())
                            ->where('code', $request->code)
                            ->where('expiration', '>', now());

        if ($verification->doesntExist()) {
            return [
                'status' => 410,
                'message' => 'Verification code already expired.',
            ];
        }

        $user = User::find($verification->first()->user_id);

        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 409,
                'message' => 'You have already verified your account.',
            ];
        }
        
        $user->markEmailAsVerified();

        return [
            'status' => 200,
            'message' => 'You have successfully verified your account.',
        ];
    }

    /**
     * Resend another verification code to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function resendVerificationCode(Request $request): array
    {
        $user = User::whereUsername($request->username)->first();
        
        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 409,
                'message' => 'You have already verified your account.',
            ];
        }

        $type = $this->sendVerificationCode($user, $request->prefers_sms);

        return [
            'status' => 200,
            'message' => "A verification code has been sent your {$type}.",
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
        $query = DB::table('password_resets')->where('email', $emailAddress)->whereNotNull('completed_at');
        
        if ($this->rateLimitReached($query, 7, 24, 'completed_at')) {
            return [
                'status' => 429,
                'message' => "You're doing too much. Try again later.",
            ];
        }

        try {
            $type = DB::transaction(function() use ($request, $emailAddress) {
                $user = User::firstWhere('email', $emailAddress);
                $token = Hash::make($emailAddress);
                $prefersSMS = $request->boolean('prefers_sms');
                $url = config('app.client_url') . "/reset-password/{$token}";
                
                DB::table('password_resets')->updateOrInsert(
                    [
                        'email' => $emailAddress,
                        'completed_at' => null,
                    ],
                    [
                        'token' => $token,
                        'expiration' => now()->addMinutes(30),
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
        $user = User::where('email', $request->input('email'));
        $pr = DB::table('password_resets')
                ->where('token', $request->input('token'))
                ->where('expiration', '>', now())
                ->whereNull('completed_at');

        if ($pr->doesntExist()) {
            return [
                'status' => 401,
                'message' => 'Invalid token.',
            ];
        }

        if (!Hash::check($request->input('email'), $request->input('token'))) {
            return [
                'status' => 401,
                'message' => 'Invalid email address.',
            ];
        }

        try {
            DB::transaction(function() use ($request, $user, $pr) {
                $user->update(['password' => Hash::make($request->input('password'))]);
    
                $pr->update(['completed_at' => now()]);
        
                event(new PasswordReset($user->first()));
            });
    
            return $this->authenticateUser(
                $user->firstWithBasicOnly(),
                'You have successfully reset your password.'
            );
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