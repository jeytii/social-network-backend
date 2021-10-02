<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
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
    Route::get('/', [UserController::class, 'get'])->name('get'); // FIXME: Change the method name and route name
    Route::get('/random', [UserController::class, 'getRandom'])->name('get.random');
    Route::get('/search', [UserController::class, 'search'])->name('search');
    Route::post('/follow/{user}', [UserController::class, 'follow'])->name('follow');
    Route::delete('/unfollow/{user}', [UserController::class, 'unfollow'])->name('unfollow');
});

Route::prefix('profile')->name('profile.')->group(function() {
    Route::prefix('{username}')->name('get.')->group(function() {
        Route::get('/', [ProfileController::class, 'getInfo'])->name('info');
        Route::get('posts', [ProfileController::class, 'getPosts'])->name('posts');
        Route::get('comments', [ProfileController::class, 'getComments'])->name('comments');
        Route::get('likes', [ProfileController::class, 'getLikes'])->name('likes');
        Route::get('bookmarks', [ProfileController::class, 'getBookmarks'])->name('bookmarks');
        Route::get('followers', [ProfileController::class, 'getFollowers'])->name('followers');
        Route::get('following', [ProfileController::class, 'getFollowedUsers'])->name('following');
    });
    
    Route::post('upload/profile-photo', [ProfileController::class, 'uploadProfilePhoto'])->name('upload');
    Route::put('update', [ProfileController::class, 'update'])->name('update');
});

// FIXME: Group common routes into a resource
Route::prefix('posts')->name('posts.')->group(function() {
    Route::get('/', [PostController::class, 'get'])->name('get');
    Route::get('/sort', [PostController::class, 'sort'])->name('sort');
    Route::post('/', [PostController::class, 'store'])->name('create');
    Route::put('{post}', [PostController::class, 'update'])->name('update');
    Route::delete('{post}', [PostController::class, 'destroy'])->name('delete');
    Route::post('{post}/like', [PostController::class, 'like'])->name('like');
    Route::delete('{post}/dislike', [PostController::class, 'dislike'])->name('dislike');
    Route::post('{post}/bookmark', [PostController::class, 'bookmark'])->name('bookmark');
    Route::delete('{post}/unbookmark', [PostController::class, 'unbookmark'])->name('unbookmark');
});

Route::apiResource('comments', CommentController::class)->except('show');

Route::prefix('settings')->name('settings.')->group(function() {
    Route::prefix('request-update')->name('request-update.')->group(function() {
        Route::post('username', [SettingController::class, 'requestUsernameUpdate'])->name('username');
    });

    Route::prefix('update')->name('update.')->group(function() {
        Route::put('username', [SettingController::class, 'updateUsername'])->name('username');
    });
});

Route::prefix('notifications')->name('notifications.')->group(function() {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::put('/peek', [NotificationController::class, 'peek'])->name('peek');
    Route::put('/{id}/read', [NotificationController::class, 'read'])->name('read');
    Route::put('/read/all', [NotificationController::class, 'readAll'])->name('read.all');
});

// TODO: Add logout
