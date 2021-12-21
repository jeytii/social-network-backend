<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(2)->hasPosts(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('posts')->truncate();
});

test('Should successfully delete a post', function() {
    $post = $this->user->posts()->first()->slug;

    $this->response
        ->deleteJson(route('posts.destroy', compact('post')))
        ->assertOk();

    $this->assertTrue($this->user->posts()->where('slug', $post)->doesntExist());
});

test('Should not be able to delete other user\'s post', function() {
    $user = User::firstWhere('id', '!=', $this->user->id);
    $post = $user->posts()->first()->slug;

    $this->response
        ->deleteJson(route('posts.destroy', compact('post')))
        ->assertForbidden();

    $this->assertTrue($user->posts()->where('slug', $post)->exists());
});
