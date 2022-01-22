<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Cache};

beforeAll(function() {
    User::factory()->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should throw an error if body\'s length is greater than maximum length', function () {
    $this->response
        ->postJson(route('posts.store'), [
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['The number of characters exceeds the maximum length.']);
});

test('Should successfully create a post', function() {
    $this->response
        ->postJson(route('posts.store'), [
            'body' => 'Sample post'
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'slug',
                'body',
                'likes_count',
                'comments_count',
                'timestamp',
                'is_own_post',
                'is_liked',
                'is_edited',
                'is_bookmarked',
                'user' => config('api.response.user.basic')
            ],
            'status',
        ]);

    $this->assertTrue($this->user->posts()->where('body', 'Sample post')->exists());
});
