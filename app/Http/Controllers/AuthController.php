<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        abort_if(!Auth::attempt($credentials), 422, 'Cannot find username and password combination.');

        abort_if(!$request->user()->hasVerifiedEmail(), 401, 'Your account is not yet verified.');

        $token = $request->user()->createToken(env('SANCTUM_SECRET_KEY'))->plainTextToken;

        return response()->json(compact('token'));
    }
}
