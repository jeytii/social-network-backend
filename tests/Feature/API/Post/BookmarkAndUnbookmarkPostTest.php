<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Cache};

beforeAll(function() {
    User::factory(2)->hasPosts(2)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should be able to bookmark a post', function() {
    $post = DB::table('posts')->where('user_id', '!=', $this->user->id)->first();

    $this->response
        ->postJson(route('posts.bookmark', ['post' => $post->slug]))
        ->assertOk();

    $this->assertTrue($this->user->bookmarks()->whereKey($post->id)->exists());
});

test('Should not be able to bookmark a post that has already been bookmarked', function() {
    $post = DB::table('posts')->where('user_id', '!=', $this->user->id)->first();

    // Suppost the selected post has already been bookmarked based on the test above.
    $this->response
        ->postJson(route('posts.bookmark', ['post' => $post->slug]))
        ->assertForbidden();

    $this->assertDatabaseCount('bookmarks', 1);
});

test('Should be able to unbookmark a post', function() {
    $post = DB::table('posts')->where('user_id', '!=', $this->user->id)->first();

    // Suppost the selected post has already been bookmarked based on the test above.
    $this->response
        ->deleteJson(route('posts.unbookmark', ['post' => $post->slug]))
        ->assertOk();

    $this->assertTrue($this->user->bookmarks()->whereKey($post->id)->doesntExist());
});

test('Should not be able to unbookmark a post that is not bookmarked', function() {
    $post = DB::table('posts')->where('user_id', '!=', $this->user->id)->first();

    $this->response
        ->deleteJson(route('posts.unbookmark', ['post' => $post->slug]))
        ->assertForbidden();

    $this->assertTrue($this->user->bookmarks()->whereKey($post->id)->doesntExist());
});
