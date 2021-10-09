<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    PostController,
    CommentController,
    NotificationController,
    ProfileController,
    SettingController
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

Route::prefix('users')->name('users.')->group(function() {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/random', [UserController::class, 'getRandom'])->name('get.random');
    Route::get('/search', [UserController::class, 'search'])->name('search');
    Route::post('/follow/{user}', [UserController::class, 'follow'])->name('follow');
    Route::delete('/unfollow/{user}', [UserController::class, 'unfollow'])->name('unfollow');
});

Route::prefix('profile')->name('profile.')->group(function() {
    Route::prefix('{user:username}')->name('get.')->group(function() {
        $throw404Error = function () {
            return response()->json([
                'status' => 404,
                'message' => 'User not found.'
            ], 404);
        };

        Route::get('/', [ProfileController::class, 'getInfo'])->missing($throw404Error)->name('info');
        Route::get('posts', [ProfileController::class, 'getPosts'])->missing($throw404Error)->name('posts');
        Route::get('comments', [ProfileController::class, 'getComments'])->missing($throw404Error)->name('comments');
        Route::get('likes/posts', [ProfileController::class, 'getLikedPosts'])->missing($throw404Error)->name('likes.posts');
        Route::get('likes/comments', [ProfileController::class, 'getLikedComments'])->missing($throw404Error)->name('likes.comments');
        Route::get('bookmarks', [ProfileController::class, 'getBookmarks'])->missing($throw404Error)->name('bookmarks');
        Route::get('followers', [ProfileController::class, 'getFollowers'])->missing($throw404Error)->name('followers');
        Route::get('following', [ProfileController::class, 'getFollowedUsers'])->missing($throw404Error)->name('following');
    });
    
    Route::post('upload/profile-photo', [ProfileController::class, 'uploadProfilePhoto'])->name('upload.profile-photo');
    Route::put('update', [ProfileController::class, 'update'])->name('update');
});

Route::apiResource('posts', PostController::class)->except('show');
Route::prefix('posts')->name('posts.')->group(function() {
    Route::get('/sort', [PostController::class, 'sort'])->name('sort');
    Route::post('{post}/like', [PostController::class, 'like'])->name('like');
    Route::delete('{post}/dislike', [PostController::class, 'dislike'])->name('dislike');
    Route::post('{post}/bookmark', [PostController::class, 'bookmark'])->name('bookmark');
    Route::delete('{post}/unbookmark', [PostController::class, 'unbookmark'])->name('unbookmark');
});

Route::apiResource('comments', CommentController::class)->except('show');
Route::prefix('comments')->name('comments.')->group(function() {
    Route::post('{comment}/like', [CommentController::class, 'like'])->name('like');
    Route::delete('{comment}/dislike', [CommentController::class, 'dislike'])->name('dislike');
});

Route::prefix('settings')->name('settings.')->group(function() {
    Route::prefix('request-update')->name('request-update.')->group(function() {
        Route::post('username', [SettingController::class, 'requestUsernameUpdate'])->name('username');
        Route::post('email', [SettingController::class, 'requestEmailAddressUpdate'])->name('email');
        Route::post('phone', [SettingController::class, 'requestPhoneNumberUpdate'])->name('phone-number');
    });

    Route::prefix('update')->name('update.')->group(function() {
        Route::put('username', [SettingController::class, 'updateUsername'])->name('username');
        Route::put('email', [SettingController::class, 'updateEmailAddress'])->name('email');
        Route::put('phone', [SettingController::class, 'updatePhoneNumber'])->name('phone-number');
        Route::put('password', [SettingController::class, 'updatePassword'])->name('password');
    });
});

Route::prefix('notifications')->name('notifications.')->group(function() {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::put('/peek', [NotificationController::class, 'peek'])->name('peek');
    Route::put('/{id}/read', [NotificationController::class, 'read'])->name('read');
    Route::put('/read/all', [NotificationController::class, 'readAll'])->name('read.all');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
