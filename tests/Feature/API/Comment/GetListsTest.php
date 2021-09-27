<?php

use App\Models\{User, Post};
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

test('Should throw an error if a post slug doesn\'t exist', function() {
    $this->response
        ->getJson('/api/comments?pid=10000')
        ->assertNotFound();
});

test('Should return an empty list if a selected post doesn\'t have any comment', function() {
    $slug = Post::doesntHave('comments')->first()->slug;

    $this->response
        ->getJson("/api/comments?pid={$slug}&page=1")
        ->assertOk()
        ->assertExactJson([
            'data' => [],
            'has_more' => false,
            'next_offset' => null,
        ]);

    $this->response
        ->getJson("/api/comments?pid={$slug}&page=2")
        ->assertOk()
        ->assertExactJson([
            'data' => [],
            'has_more' => false,
            'next_offset' => null,
        ]);
});

test('Should return a paginated list of comments under a specific post', function() {
    $comments = collect(range(1,8))->map(fn() => [
        'post_id' => 1,
        'body' => $this->faker->words(3, true),
    ])->toArray();
    
    $this->user->comments()->createMany($comments);

    $slug = Post::first()->slug;
    
    // Suppose the selected post only has less than 20 comments
    $this->response
        ->getJson("/api/comments?pid={$slug}&page=1")
        ->assertOk()
        ->assertJsonCount(8, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'slug',
                    'body',
                    'is_own_comment',
                    'is_edited',
                    'timestamp',
                    'user' => array_merge(config('api.response.user.basic'), ['slug'])
                ]
            ],
            'has_more',
            'next_offset'
        ]);

    $this->response
        ->getJson("/api/comments?pid={$slug}&page=2")
        ->assertOk()
        ->assertExactJson([
            'data' => [],
            'has_more' => false,
            'next_offset' => null,
        ]);
});

test('Should return more comments upon clicking "show more"', function() {
    $slug = Post::first()->slug;

    $this->response
        ->getJson("/api/comments/more?pid={$slug}&page=1")
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2);

    $this->response
        ->getJson("/api/comments/more?pid={$slug}&page=2")
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson("/api/comments/more?pid={$slug}&page=3")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
