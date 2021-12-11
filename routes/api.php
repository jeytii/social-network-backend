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
    Route::get('/auth', [UserController::class, 'getAuthUser'])->name('auth');
    Route::get('/params/{column}', [UserController::class, 'getParams'])->name('get.params');
    Route::get('/random', [UserController::class, 'getRandom'])->name('get.random');
    Route::get('/search', [UserController::class, 'search'])->name('search');
    Route::post('/{user}/follow', [UserController::class, 'follow'])->name('follow');
    Route::delete('/{user}/unfollow', [UserController::class, 'unfollow'])->name('unfollow');
});

Route::prefix('profile')->name('profile.')->group(function() {
    Route::get('likes/posts', [ProfileController::class, 'getLikedPosts'])->name('likes.posts');
    Route::get('likes/comments', [ProfileController::class, 'getLikedComments'])->name('likes.comments');
    Route::get('bookmarks', [ProfileController::class, 'getBookmarks'])->name('bookmarks');
    
    Route::post('upload/profile-photo', [ProfileController::class, 'uploadProfilePhoto'])->name('upload.profile-photo');
    Route::put('update', [ProfileController::class, 'update'])->name('update');
    
    Route::prefix('{user:username}')->name('get.')->group(function() {
        Route::get('/', [ProfileController::class, 'getInfo'])->name('info');
        Route::get('posts', [ProfileController::class, 'getPosts'])->name('posts');
        Route::get('comments', [ProfileController::class, 'getComments'])->name('comments');
        Route::get('followers', [ProfileController::class, 'getFollowers'])->name('followers');
        Route::get('following', [ProfileController::class, 'getFollowedUsers'])->name('following');
    });
});

Route::apiResource('posts', PostController::class);
Route::prefix('posts')->name('posts.')->group(function() {
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
    Route::prefix('change')->name('change.')->group(function() {
        Route::put('username', [SettingController::class, 'changeUsername'])->name('username');
        Route::put('email', [SettingController::class, 'changeEmailAddress'])->name('email');
        Route::put('phone', [SettingController::class, 'changePhoneNumber'])->name('phone-number');
        Route::put('password', [SettingController::class, 'changePassword'])->name('password');
    });
});

Route::prefix('notifications')->name('notifications.')->group(function() {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/count', [NotificationController::class, 'getCount'])->name('count');
    Route::put('/peek', [NotificationController::class, 'peek'])->name('peek');
    Route::put('/{notification}/read', [NotificationController::class, 'read'])->name('read');
    Route::put('/read/all', [NotificationController::class, 'readAll'])->name('read.all');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
