<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

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

test('Should return the paginated list of owned posts', function() {
    $this->response
        ->getJson("/api/profile/{$this->user->username}/posts")
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'slug',
                    'body',
                    'likes_count',
                    'comments_count',
                    'timestamp',
                    'is_own_post',
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
        ]);

    $this->response
        ->getJson("/api/profile/{$this->user->username}/posts?page=2")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of liked posts', function() {
    $this->user->likes()->sync(range(11, 15));

    $this->response
        ->getJson("/api/profile/{$this->user->username}/likes")
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'slug',
                    'body',
                    'likes_count',
                    'comments_count',
                    'timestamp',
                    'is_own_post',
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
        ]);

    $this->response
        ->getJson("/api/profile/{$this->user->username}/likes?page=2")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of bookmarked posts', function() {
    $this->user->bookmarks()->sync(range(11, 15));

    $this->response
        ->getJson("/api/profile/{$this->user->username}/bookmarks")
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'slug',
                    'body',
                    'likes_count',
                    'comments_count',
                    'timestamp',
                    'is_own_post',
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
        ]);

    $this->response
        ->getJson("/api/profile/{$this->user->username}/bookmarks?page=2")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
