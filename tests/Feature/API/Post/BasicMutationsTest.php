<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(5)->hasPosts(5)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should not create a post if body is blank or not set', function () {
    $this->response
        ->postJson('/api/posts')
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Post should not be blank.']);
});

test('Should not create a post if body length is greater than maximum length', function () {
    $this->response
        ->postJson('/api/posts', [
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Maximum character length is 180.']);
});

test('Should successfully create a post', function() {
    $this->response
        ->postJson('/api/posts', [
            'body' => 'A newly-created post'
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'post' => [
                    'slug',
                    'body',
                    'likes_count',
                    'comments_count',
                    'timestamp',
                    'is_own_post',
                    'is_liked',
                    'is_edited',
                    'is_bookmarked',
                    'user' => array_merge(config('api.response.user.basic'), ['slug'])
                ],
            ]
        ]);
});

test('Should successfully update a post', function() {
    $slug = $this->user->posts()->first()->slug;

    $this->response
        ->putJson("/api/posts/$slug", [
            'body' => 'Hello World'
        ])
        ->assertOk()
        ->assertExactJson([
            'updated' => true,
            'message' => 'Post successfully updated.',
        ]);

    $this->assertDatabaseHas('posts', [
        'body' => 'Hello World'
    ]);
});

test('Should not be able to update other user\'s post', function() {
    $slug = User::find(3)->posts()->first()->slug;

    $this->response
        ->putJson("/api/posts/$slug", [
            'body' => 'Hello World'
        ])
        ->assertForbidden();
    
    $this->assertDatabaseMissing('posts', [
        'user_id' => 3,
        'body' => 'Hello World',
    ]);
});

test('Should successfully delete a post', function() {
    $slug = $this->user->posts()->first()->slug;

    $this->response
        ->deleteJson("/api/posts/$slug")
        ->assertOk()
        ->assertExactJson([
            'deleted' => true,
            'message' => 'Post successfully deleted.',
        ]);

    $this->assertDatabaseMissing('posts', compact('slug'));
});

test('Should not be able to delete other user\'s post', function() {
    $slug = User::find(3)->posts()->first()->slug;

    $this->response
        ->deleteJson("/api/posts/$slug")
        ->assertForbidden();

    $this->assertDatabaseHas('posts', compact('slug'));
});