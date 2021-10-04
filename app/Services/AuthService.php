<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\{RegistrationRequest, ResendCodeRequest, ResetPasswordRequest};
use Illuminate\Support\Facades\{DB, Hash, Password};
use Illuminate\Auth\Events\{Login, Registered, PasswordReset};
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
    private function authenticateUser(User $user, string $message): array
    {
        $token = $user->createToken($user->username)->plainTextToken;
        $status = 200;

        return compact('status', 'message', 'user', 'token');
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
        $user = User::whereUser($request->username);

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
     * @param \App\Http\Requests\RegistrationRequest  $request
     * @return array
     */
    public function register(RegistrationRequest $request): array
    {
        $body = $request->only([
            'name', 'email', 'username', 'phone_number',
            'gender', 'birth_month', 'birth_day', 'birth_year'
        ]);
        $password = Hash::make($request->password);
        $user = User::create(array_merge($body, compact('password')));

        event(new Registered($user));
        
        $type = $this->sendVerificationCode($user, $request->prefers_sms_verification);

        return [
            'status' => 201,
            'message' => "Account successfully created. Please enter the verification code that was sent to your {$type}.",
        ];
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
     * @param \App\Http\Requests\ResendCodeRequest  $request
     * @return array
     */
    public function resendVerificationCode(ResendCodeRequest $request): array
    {
        $user = User::whereUser($request->username)->first();
        
        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 409,
                'message' => 'You have already verified your account.',
            ];
        }

        $type = $this->sendVerificationCode($user, $request->prefers_sms_verification);

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
     */
    public function sendPasswordResetLink(Request $request): array
    {
        if (User::firstWhere('email', $request->email)->hasVerifiedEmail()) {
            return [
                'status' => 409,
                'message' => 'You have already verified your account.',
            ];
        }

        Password::sendResetLink($request->only('email'));

        return [
            'status' => 200,
            'message' => 'Please check for the link that has been sent to your email address.',
        ];
    }

    /**
     * Reset user's password.
     * 
     * @param \App\Http\Requests\ResetPasswordRequest  $request
     * @return array
     */
    public function resetPassword(ResetPasswordRequest $request): array
    {
        $user = User::where('email', $request->email)->firstWithBasicOnly();

        $status = Password::reset($request->validated(), function($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ]);

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::INVALID_TOKEN) {
            return [
                'status' => 401,
                'message' => 'Permission denied. You entered an invalid token.',
            ];
        }

        return $this->authenticateUser($user, 'You have successfully reset your password.');
    }
}