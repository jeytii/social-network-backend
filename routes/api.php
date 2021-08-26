<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, UserController};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/users', [UserController::class, 'get']);
    Route::get('/users/suggested', [UserController::class, 'getSuggested']);
    Route::put('/users/auth/update', [UserController::class, 'update']);
    Route::post('/users/follow/{user}', [UserController::class, 'follow']);
    Route::delete('/users/unfollow/{user}', [UserController::class, 'unfollow']);
    Route::get('/users/connections', [UserController::class, 'getConnections']);
});
