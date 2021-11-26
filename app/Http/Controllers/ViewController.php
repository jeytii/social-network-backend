<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewController extends Controller
{
    protected $user;

    /**
     * Create a new controller instance.
     *
     * @param \App\Repositories\UserRepository  $user
     * @return void
     */
    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    /**
     * Check user is logged in.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateUser(Request $request)
    {
        $response = $this->user->getAuthUser($request);

        return response()->json($response, $response['status']);
    }

    /**
     * Check if a specific post exists.
     * 
     * @param \App\Models\Post  $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticatePost(Post $post)
    {
        return response()->json(['status' => 200]);
    }

    /**
     * Check if a specific verification token exists.
     * 
     * @param string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateVerificationToken(string $token)
    {
        $status = 200;
        $verification = DB::table('verifications')
                            ->where('token', $token)
                            ->where('expiration', '>', now());

        if ($verification->doesntExist()) {
            $status = 404;
        }

        return response()->json(compact('status'), $status);
    }

    /**
     * Check if a specific reset password token exists.
     * 
     * @param string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateResetPasswordToken(string $token)
    {
        $status = 200;
        $invalidToken = DB::table('password_resets')
                            ->where('token', $token)
                            ->where('expiration', '>', now())
                            ->whereNull('completed_at')
                            ->doesntExist();

        if ($invalidToken) {
            $status = 404;
        }

        return response()->json(compact('status'), $status);
    }
}
