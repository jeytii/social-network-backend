<?php

use Illuminate\Support\Facades\{Route, Broadcast};
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

Broadcast::routes();

Route::controller(UserController::class)
    ->prefix('users')
    ->name('users.')
    ->group(function() {
        Route::get('/', 'index')->name('index');
        Route::get('/random', 'getRandom')->name('get.random');
        Route::get('/search', 'search')->name('search');
        Route::post('/{user}/follow', 'follow')->can('follow', 'user')->name('follow');
        Route::delete('/{user}/unfollow', 'unfollow')->can('unfollow', 'user')->name('unfollow');
    });

Route::controller(ProfileController::class)
    ->prefix('profile')
    ->name('profile.')
    ->group(function() {
        Route::get('likes/posts', 'getLikedPosts')->name('likes.posts');
        Route::get('likes/comments', 'getLikedComments')->name('likes.comments');
        Route::get('bookmarks', 'getBookmarks')->name('bookmarks');
        
        Route::post('upload/profile-photo', 'uploadProfilePhoto')->name('upload.profile-photo');
        Route::put('update', 'update')->name('update');
        
        Route::prefix('{user:username}')->name('get.')->group(function() {
            Route::get('/', 'getInfo')->name('info');
            Route::get('posts', 'getPosts')->name('posts');
            Route::get('comments', 'getComments')->name('comments');
            Route::get('followers', 'getFollowers')->name('followers');
            Route::get('following', 'getFollowedUsers')->name('following');
        });
    });

Route::apiResource('posts', PostController::class);
Route::controller(PostController::class)
    ->prefix('posts')
    ->name('posts.')
    ->group(function() {
        Route::post('{post}/like', 'like')->can('like', 'post')->name('like');
        Route::delete('{post}/dislike', 'dislike')->can('dislike', 'post')->name('dislike');
        Route::post('{post}/bookmark', 'bookmark')->can('bookmark', 'post')->name('bookmark');
        Route::delete('{post}/unbookmark', 'unbookmark')->can('unbookmark', 'post')->name('unbookmark');
    });

Route::apiResource('comments', CommentController::class)->except('show');
Route::controller(CommentController::class)
    ->prefix('comments')
    ->name('comments.')
    ->group(function() {
        Route::post('{comment}/like', 'like')->can('like', 'comment')->name('like');
        Route::delete('{comment}/dislike', 'dislike')->can('dislike', 'comment')->name('dislike');
    });

Route::controller(SettingController::class)
    ->prefix('settings')
    ->name('settings.')
    ->group(function() {
        Route::prefix('change')->name('change.')->group(function() {
            Route::put('username', 'changeUsername')->name('username');
            Route::put('email', 'changeEmailAddress')->name('email');
            Route::put('password', 'changePassword')->name('password');
            Route::put('color', 'changeColor')->name('color');
        });

        Route::put('dark-mode', 'toggleDarkMode')->name('dark-mode.toggle');
    });

Route::controller(NotificationController::class)
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function() {
        Route::get('/', 'index')->name('index');
        Route::get('/count', 'getCount')->name('count');
        Route::put('/peek', 'peek')->name('peek');
        Route::put('/{notification}/read', 'read')->name('read');
        Route::put('/read/all', 'readAll')->name('read.all');
    });

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

