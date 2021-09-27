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

Route::middleware('guest')->name('auth.')->group(function() {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::put('/verify', [AuthController::class, 'verify'])->name('verify');

    Route::post('/verify/resend', [AuthController::class, 'resendVerificationCode'])
        ->middleware('throttle:3,30')
        ->name('verify.resend');
    
    Route::post('/forgot-password', [AuthController::class, 'requestPasswordReset'])->name('forgot-password');
    Route::put('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
});
