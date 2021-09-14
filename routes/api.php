<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    PostController,
    CommentController,
    ProfileController
};

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

Route::middleware('guest')->group(function() {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::put('/verify', [AuthController::class, 'verify']);
    Route::post('/verify/resend', [AuthController::class, 'resendVerificationCode'])->middleware('throttle:3,30');
    Route::post('/forgot-password', [AuthController::class, 'requestPasswordReset']);
    Route::put('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function() {
    Route::prefix('users')->group(function() {
        Route::get('/', [UserController::class, 'get']);
        Route::get('/suggested', [UserController::class, 'getSuggested']);
        Route::get('/search', [UserController::class, 'search']);
        Route::put('/auth/update', [UserController::class, 'update']);
        Route::post('/follow/{user}', [UserController::class, 'follow']);
        Route::delete('/unfollow/{user}', [UserController::class, 'unfollow']);
    });

    Route::prefix('posts')->group(function() {
        Route::get('/', [PostController::class, 'get']);
        Route::post('/', [PostController::class, 'store']);
        Route::put('{post}', [PostController::class, 'update']);
        Route::delete('{post}', [PostController::class, 'destroy']);
        Route::post('{post}/like', [PostController::class, 'like']);
        Route::delete('{post}/dislike', [PostController::class, 'dislike']);
        Route::post('{post}/bookmark', [PostController::class, 'bookmark']);
        Route::delete('{post}/unbookmark', [PostController::class, 'unbookmark']);
    });

    Route::prefix('comments')->group(function() {
        Route::get('/', [CommentController::class, 'get']);
        Route::get('/more', [CommentController::class, 'getMoreOwnComments']);
        Route::post('/', [CommentController::class, 'store']);
        Route::put('{comment}', [CommentController::class, 'update']);
        Route::delete('{comment}', [CommentController::class, 'destroy']);
    });

    Route::prefix('profile')->group(function() {
        Route::get('{username}/{section}', [ProfileController::class, 'get'])
            ->where('section', 'posts|likes|comments|bookmarks|followers|following');
        Route::get('{username}', [ProfileController::class, 'getInfo']);
        Route::put('update', [ProfileController::class, 'update']);
    });
});
