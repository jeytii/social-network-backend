<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

Route::middleware('guest')->group(function() {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::put('/verify', [AuthController::class, 'verify']);
    Route::post('/verify/resend', [AuthController::class, 'resendVerificationCode'])->middleware('throttle:3,30');
    Route::post('/forgot-password', [AuthController::class, 'requestPasswordReset']);
    Route::put('/reset-password', [AuthController::class, 'resetPassword']);
});
