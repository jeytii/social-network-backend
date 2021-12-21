<?php

use App\Models\{User, Post, Comment};
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(3)->hasPosts(3)->create();
    
    Comment::factory(35)
        ->for(User::first())
        ->for(Post::first())
        ->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('comments')->truncate();
});

test('Should return a paginated list of comments under a specific post', function() {
    $slug = DB::table('posts')->first()->slug;
    
    $this->response
        ->getJson(route('comments.index', ['post' => $slug, 'page' => 1]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'items' => [
                '*' => [
                    'slug',
                    'post_slug',
                    'body',
                    'is_own_comment',
                    'is_liked',
                    'is_edited',
                    'timestamp',
                    'user' => config('api.response.user.basic')
                ]
            ],
            'has_more',
            'next_offset',
            'status',
        ]);

    $this->response
        ->getJson(route('comments.index', ['post' => $slug, 'page' => 2]))
        ->assertOk()
        ->assertJsonCount(15, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson(route('comments.index', ['post' => $slug, 'page' => 3]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
