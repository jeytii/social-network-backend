<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Cache};

beforeAll(function() {
    User::factory(2)->hasPosts()->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should successfully update a post', function() {
    $post = $this->user->posts()->first()->slug;

    $this->response
        ->putJson(route('posts.update', compact('post')), [
            'body' => 'Hello World'
        ])
        ->assertOk();

    $this->assertTrue($this->user->posts()->where('body', 'Hello World')->exists());
});

test('Should not be able to update other user\'s post', function() {
    $user = User::firstWhere('id', '!=', $this->user->id);
    $post = $user->posts()->first()->slug;

    $this->response
        ->putJson(route('posts.update', compact('post')), [
            'body' => 'Hello World'
        ])
        ->assertForbidden();

    $this->assertTrue($user->posts()->where('body', 'Hello World')->doesntExist());
});
