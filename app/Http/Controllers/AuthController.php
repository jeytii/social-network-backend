<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\{AuthRequest, UserRequest};
use Illuminate\Support\Facades\{DB, Hash};
use App\Notifications\ResetPassword;
use App\Services\AuthService;
use Exception;

class AuthController extends Controller
{
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @param \App\Services\AuthService  $auth
     * @return void
     */
    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Log in a user.
     *
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(AuthRequest $request)
    {
        $user = User::whereUsername($request->input('username'));

        if (
            !$user->exists() ||
            !Hash::check($request->input('password'), $user->first()->password)
        ) {
            return response()->error('Incorrect combination.', 404);
        }

        if (!$user->first()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your account is not yet verified.',
                'data' => $request->only('username'),
            ], 401);
        }

        return response()->json(
            $this->auth->authenticate($user->first(), 'Login successful')
        );
    }

    /**
     * Register a user.
     * 
     * @param \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(UserRequest $request)
    {
        $body = $request->except('password', 'password_confirmation');
        $password = Hash::make($request->input('password'));
        
        try {
            DB::transaction(function() use ($request, $body, $password) {
                $user = User::create(array_merge($body, compact('password')));
                
                $this->auth->sendVerificationCode($user);
            });
    
            return response()->success(201);
        }
        catch (Exception $exception) {
            return response()->somethingWrong();
        }
    }

    /**
     * Verify a user.
     * 
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(AuthRequest $request)
    {
        $cacheKey = "verification.{$request->input('token')}";
        $verification = cache($cacheKey);

        if (!cache()->has($cacheKey) || $request->input('code') !== $verification['code']) {
            return response()->error('Invalid verification code.', 401);
        }

        $user = User::find($verification['id']);

        if ($user->hasVerifiedEmail()) {
            return response()->error('You have already verified your account.', 409);
        }
        
        try {
            $response = DB::transaction(function() use ($user) {
                $user->markEmailAsVerified();

                return $this->auth->authenticate($user, 'You have successfully verified your account.');
            });

            cache()->forget($cacheKey);

            return response()->json($response);
        }
        catch (Exception $exception) {
            return response()->somethingWrong();
        }
    }

    /**
     * Resend another verification code to the user.
     * 
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationCode(AuthRequest $request)
    {
        $user = User::whereUsername($request->input('username'))->first();
        
        if ($user->hasVerifiedEmail()) {
            return response()->error('You have already verified your account.', 409);
        }
        
        $this->auth->sendVerificationCode($user);

        return response()->success();
    }

    /**
     * Send a password-reset request link to the user.
     * 
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestPasswordReset(AuthRequest $request)
    {
        $emailAddress = $request->input('email');
        $user = User::firstWhere('email', $emailAddress);
        
        if ($user->passwordResetLimitReached()) {
            return response()->error("You're doing too much. Try again later.", 429);
        }

        $token = bin2hex(random_bytes(16));

        try {
            DB::transaction(function() use ($user, $token) {
                DB::table('password_resets')->updateOrInsert(
                    [
                        'email' => $user->email,
                        'completed_at' => null
                    ],
                    compact('token')
                );

                $user->notify(new ResetPassword(config('app.frontend_url') . "/reset-password/{$token}"));
            });

            cache(
                ["password-reset.{$token}" => $emailAddress],
                60 * config('validation.expiration.password_reset')
            );
    
            // "Please check for the link that has been sent to your email address."
            return response()->success();
        }
        catch (Exception $exception) {
            return response()->somethingWrong();
        }
    }

     /**
     * Reset user's password.
     * 
     * @param \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(AuthRequest $request)
    {
        $token = $request->input('token');
        $cacheKey = "password-reset.{$token}";
        
        if (!cache()->has($cacheKey)) {
            return response()->error('Invalid token.', 401);
        }

        $user = User::firstWhere('email', cache($cacheKey));

        try {
            $response = DB::transaction(function() use ($request, $user, $token) {
                $user->update(['password' => Hash::make($request->input('password'))]);

                DB::table('password_resets')
                    ->where('token', $token)
                    ->update(['completed_at' => now()]);

                return $this->auth->authenticate($user, 'You have successfully reset your password.');
            });

            cache()->forget($cacheKey);
    
            return response()->json($response);
        }
        catch (Exception $exception) {
            return response()->somethingWrong();
        }
    }

    /**
     * Log out a user.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->success();
    }
}
