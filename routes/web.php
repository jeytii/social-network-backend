<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ViewController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/private', function(Request $request) {
    return response()->json([
        'status' => 200,
        'user' => $request->user()->only(array_merge(config('api.response.user.basic'), ['email', 'phone_number']))
    ]);
})->middleware('auth:sanctum');

Route::get('/post/{post}', [ViewController::class, 'authenticatePost']);
Route::get('/reset-password/{token}', [ViewController::class, 'authenticateResetPasswordToken']);
Route::get('/verify/{token}', [ViewController::class, 'authenticateVerificationToken']);

Route::middleware('guest')->name('auth.')->group(function() {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::put('/verify', [AuthController::class, 'verify'])->name('verify');

    Route::post('/verify/resend', [AuthController::class, 'resendVerificationCode'])->name('verify.resend');
    
    Route::post('/forgot-password', [AuthController::class, 'requestPasswordReset'])->name('forgot-password');
    Route::put('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
});
