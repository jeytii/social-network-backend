<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;

$jsonStructure = [
    'data' => [
        '*' => [
            'slug',
            'body',
            'likes_count',
            'comments_count',
            'timestamp',
            'is_own_post',
            'is_liked',
            'is_edited',
            'is_bookmarked',
            'user' => [
                'slug',
                'name',
                'username',
                'gender',
                'image_url'
            ]
        ],
    ],
    'has_more',
    'next_offset'
];

uses(WithFaker::class);

beforeAll(function() {
    User::factory(10)->hasPosts(5)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw a 404 error if username doesn\'t exist or the section is wrong', function() {
    $this->response
        ->getJson("/api/profile/unknownuser123/posts")
        ->assertNotFound();

    $this->response
        ->getJson("/api/profile/{$this->user->username}/wrongsection")
        ->assertNotFound();
});

test('Should return the paginated list of owned posts', function() use ($jsonStructure) {
    $this->response
        ->getJson("/api/profile/{$this->user->username}/posts?page=1")
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure($jsonStructure);

    $this->response
        ->getJson("/api/profile/{$this->user->username}/posts?page=2")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of liked posts', function() use ($jsonStructure) {
    $this->user->likes()->sync(range(11, 15));

    $this->response
        ->getJson("/api/profile/{$this->user->username}/likes?page=1")
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure($jsonStructure);

    $this->response
        ->getJson("/api/profile/{$this->user->username}/likes?page=2")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of user\'s comments on all posts', function() {
    $comments = collect(range(1,7))->map(fn() => [
        'post_id' => 1,
        'body' => $this->faker->words(3, true)
    ])->toArray();

    $this->user->comments()->createMany($comments);

    $this->response
        ->getJson("/api/profile/{$this->user->username}/comments?page=1")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'slug',
                    'body',
                    'likes_count',
                    'comments_count',
                    'timestamp',
                    'is_own_post',
                    'is_liked',
                    'is_edited',
                    'is_bookmarked',
                    'user' => [
                        'slug',
                        'name',
                        'username',
                        'gender',
                        'image_url'
                    ],
                    'comments' => [
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
                                'image_url'
                            ],
                        ]
                    ],
                ],
            ],
            'has_more',
            'next_offset'
        ]);
});

test('Should return the paginated list of bookmarked posts', function() use ($jsonStructure) {
    $this->user->bookmarks()->sync(range(11, 15));

    $this->response
        ->getJson("/api/profile/{$this->user->username}/bookmarks?page=1")
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure($jsonStructure);

    $this->response
        ->getJson("/api/profile/{$this->user->username}/bookmarks?page=2")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
