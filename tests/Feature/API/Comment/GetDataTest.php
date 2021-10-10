<?php

use App\Models\{User, Post, Comment};
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;

uses(WithFaker::class);

beforeAll(function() {
    User::factory(3)->hasPosts(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should return a paginated list of comments under a specific post', function() {
    Comment::factory(35)
        ->for($this->user)
        ->for(Post::first())
        ->create();

    $slug = Post::first()->slug;
    
    $this->response
        ->getJson(route('comments.index', ['pid' => $slug, 'page' => 1]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'items' => [
                '*' => [
                    'slug',
                    'body',
                    'is_own_comment',
                    'is_liked',
                    'is_edited',
                    'timestamp',
                    'user' => array_merge(config('api.response.user.basic'), ['slug'])
                ]
            ],
            'has_more',
            'next_offset',
            'status',
            'message',
        ]);
});

test('Should return a paginated list of comments ordered by number of likes', function() {
    $post = Post::first();
    $first = Comment::whereHas('post', fn($q) => $q->where('id', $post->id))->first();
    $second = Comment::whereHas('post', fn($q) => $q->where('id', $post->id))->firstWhere('id', '!=', $first->id);

    $first->likers()->attach(User::where('id', '!=', $this->user->id)->pluck('id'));
    $second->likers()->attach($this->user->id);
    
    $this->response
        ->getJson(route('comments.index', [
            'pid' => $post->slug,
            'page' => 1,
            'by' => 'likes',
        ]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJson([
            'items' => [
                [
                    'slug' => $first->slug,
                    'likes_count' => 2,
                ],
                [
                    'slug' => $second->slug,
                    'likes_count' => 1,
                ],
            ]
        ]);
});

test('Should successfully retrieve more comments upon clicking "show more comments"', function() {
    // Suppose there's only 15 comments left to be retrieved after the first call based on the test above.
    $slug = Post::first()->slug;

    $this->response
        ->getJson(route('comments.index', ['pid' => $slug, 'page' => 2]))
        ->assertOk()
        ->assertJsonCount(15, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
