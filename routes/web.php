<?php

use App\Http\Controllers\{AuthController, ViewController};
use Illuminate\Support\Facades\Route;

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

Route::controller(ViewController::class)->group(function() {
    Route::get('/private', 'authenticateuser')->middleware('auth:sanctum');
    Route::get('/post/{post}', 'authenticatePost');
    Route::get('/reset-password/{token}', 'authenticateResetPasswordToken');
    Route::get('/verify/{token}', 'authenticateVerificationToken');
});

Route::controller(AuthController::class)->middleware('guest')->name('auth.')->group(function() {
    Route::post('/login', 'login')->name('login');
    Route::post('/register', 'register')->name('register');
    Route::put('/verify', 'verify')->name('verify');

    Route::post('/verify/resend', 'resendVerificationCode')->name('verify.resend');
    
    Route::post('/forgot-password', 'requestPasswordReset')->name('forgot-password');
    Route::put('/reset-password', 'resetPassword')->name('reset-password');
});
