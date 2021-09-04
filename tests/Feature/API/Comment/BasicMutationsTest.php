<?php

use App\Models\{User, Comment, Post};
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

beforeEach(function() {
    User::factory(3)->hasPosts(5)->create();

    $this->user = User::first();
    $this->response = $this->actingAs($this->user);

    Sanctum::actingAs($this->user, ['*']);
});

afterEach(function() {
    DB::table('users')->truncate();
});

test('Should not create a comment if body is blank or not set', function () {
    $this->response
        ->postJson('/api/comments')
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Comment should not be blank.']);
});

test('Should not create a comment if body length is greater than maximum length', function () {
    $this->response
        ->postJson('/api/comments', [
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Maximum character length is 180.']);
});

test('Should successfully create a comment', function() {
    $slug = Post::first()->slug;
    
    $this->response
        ->postJson("/api/comments?user=$slug", [
            'body' => 'Hello World'
        ])
        ->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'comment' => [
                    'slug',
                    'body',
                    'timestamp',
                    'is_own_comment',
                    'is_edited',
                    'user' => [
                        'slug',
                        'name',
                        'username',
                        'gender',
                        'image_url'
                    ]
                ],
            ]
        ]);
});
