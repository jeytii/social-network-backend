<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

beforeEach(function() {
    User::factory(10)->hasPosts(5)->create();

    $this->user = User::first();
    $this->response = $this->actingAs($this->user);

    Sanctum::actingAs($this->user, ['*']);
});

afterEach(function() {
    DB::table('users')->truncate();
});

test('Should throw a 404 error if username doesn\'t exist or the section is wrong', function() {
    $this->response
        ->getJson("/api/posts/profile?username=unknownuser123&section=own")
        ->assertNotFound();

    $this->response
        ->getJson("/api/posts/profile?username={$this->user->username}&section=wrongsection")
        ->assertNotFound();
});

test('Should return the paginated list of owned posts', function() {
    $this->response
        ->getJson("/api/posts/profile?username={$this->user->username}&section=own")
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
        ->getJson("/api/posts/profile?page=2&username={$this->user->username}&section=own")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of liked posts', function() {
    $this->user->likes()->sync(range(11, 15));

    $this->response
        ->getJson("/api/posts/profile?username={$this->user->username}&section=likes")
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
        ->getJson("/api/posts/profile?page=2&username={$this->user->username}&section=likes")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of bookmarked posts', function() {
    $this->user->bookmarks()->sync(range(11, 15));

    $this->response
        ->getJson("/api/posts/profile?username={$this->user->username}&section=bookmarks")
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
        ->getJson("/api/posts/profile?page=2&username={$this->user->username}&section=bookmarks")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
