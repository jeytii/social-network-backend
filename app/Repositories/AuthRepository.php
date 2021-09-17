<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Password};
use Illuminate\Auth\Events\{Login, Registered, PasswordReset};
use App\Http\Requests\{RegistrationRequest, ResendCodeRequest, ResetPasswordRequest};
use App\Notifications\SendVerificationCode;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class AuthRepository implements AuthRepositoryInterface
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

        return compact('user', 'token', 'message');
    }

    /**
     * Generate a verification code for newly created user.
     * 
     * @param \App\Models\User  $user
     * @param bool  $prefersSMS
     * @return void
     */
    private function generateVerificationCode(User $user, bool $prefersSMS)
    {
        $code = random_int(100000, 999999);

        DB::table('verifications')->insert([
            'user_id' => $user->id,
            'code' => $code,
            'prefers_sms' => $prefersSMS,
            'expiration' => now()->addMinutes(10),
        ]);

        $user->notify(new SendVerificationCode($code, $prefersSMS));
    }

    /**
     * Log in a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function logInUser(Request $request): array
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::whereUser($request->username);

        if (
            !$user->exists() ||
            !Hash::check($request->password, $user->first()->password)
        ) {
            throw new ModelNotFoundException('Incorrect combination.', 404);
        }

        if (!$user->first()->hasVerifiedEmail()) {
            throw new AuthorizationException('Your account is not yet verified.', 401);
        }

        $user = $user->first(['id', 'name', 'username', 'gender', 'image_url'])
                    ->setHidden(['is_followed', 'is_self']);

        event(new Login('api', $user, true));

        return $this->authenticateUser($user, 'Login successful');
    }

    /**
     * Register a user.
     * 
     * @param \App\Http\Requests\RegistrationRequest  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function registerUser(RegistrationRequest $request): array
    {
        $body = $request->only([
            'name', 'email', 'username', 'phone_number', 'gender',
            'birth_month', 'birth_day', 'birth_year'
        ]);
        $password = Hash::make($request->password);
        $user = User::create(array_merge($body, compact('password')));

        event(new Registered($user));
        $this->generateVerificationCode($user, $request->verification == 1);

        return [
            'message' => 'Account successfully created. Please check for an email that was to your email address for verification.'
        ];
    }

    /**
     * Verify a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function verifyUser(Request $request): array
    {
        $request->validate([
            'code' => ['required', 'exists:verifications,code']
        ]);

        $verification = DB::table('verifications')->where('code', $request->code)->first();

        if ($verification->expiration < now()) {
            throw new Exception('Verification code already expired.', 410);
        }

        $user = User::find($verification->user_id);

        if ($user->hasVerifiedEmail()) {
            throw new Exception('You have already verified your account.', 409);
        }
        
        $user->markEmailAsVerified();

        return [
            'message' => 'You have successfully verified your account.'
        ];
    }

    /**
     * Resend another verification code to the user.
     * 
     * @param \App\Http\Requests\ResendCodeRequest  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resendCode(ResendCodeRequest $request): array
    {
        $user = User::whereUser($request->username)->first();
        
        if ($user->hasVerifiedEmail()) {
            throw new Exception('You have already verified your account.', 409);
        }

        $type = $request->prefers_sms ? 'phone number' : 'email address';

        $this->generateVerificationCode($user, $request->prefers_sms);

        return [
            'message' => "A verification code has been sent your {$type}."
        ];
    }

    /**
     * Send a password-reset request link to the user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function sendPasswordResetLink(Request $request): array
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email']
        ]);

        if (User::firstWhere('email', $request->email)->hasVerifiedEmail()) {
            throw new Exception('You have already verified your account.', 409);
        }

        Password::sendResetLink(['email' => $request->email]);

        return [
            'message' => 'A password-reset email was successfully sent to your email address.'
        ];
    }

    /**
     * Reset user's password.
     * 
     * @param \App\Http\Requests\ResetPasswordRequest  $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function resetPassword(ResetPasswordRequest $request): array
    {
        $user = User::where('email', $request->email)
                    ->first(['id', 'name', 'username', 'gender', 'image_url'])
                    ->setHidden(['is_followed', 'is_self']);

        $status = Password::reset($request->validated(), function($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ]);

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::INVALID_TOKEN) {
            throw new Exception('Permission denied. You entered an invalid token.', 403);
        }

        return $this->authenticateUser($user, 'You have successfully reset your password.');
    }
}