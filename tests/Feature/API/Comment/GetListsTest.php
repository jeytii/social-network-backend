<?php

use App\Models\{User, Post};
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(3)->hasPosts(3)->hasComments(3)->create();
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
    $slug = Post::has('comments')->first()->slug;
    
    // Suppose the selected post only has less than 20 comments
    $this->response
        ->getJson("/api/comments?pid={$slug}&page=1")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'slug',
                    'body',
                    'is_own_comment',
                    'is_edited',
                    'timestamp',
                    'user' => [
                        'slug',
                        'name',
                        'username',
                        'gender',
                        'image_url',
                    ]
                ]
            ],
            'has_more',
            'next_offset'
        ])
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson("/api/comments?pid={$slug}&page=2")
        ->assertOk()
        ->assertExactJson([
            'data' => [],
            'has_more' => false,
            'next_offset' => null,
        ]);
});
