<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

beforeEach(function() {
    User::factory()->hasPosts(5)->create();

    $user = User::first();
    
    $this->response = $this->actingAs($user);

    Sanctum::actingAs($user, ['*']);
});

afterEach(function() {
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
            'body' => 'Hello World'
        ])
        ->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'post' => [
                    'slug',
                    'body',
                    'likes_count',
                    'comments_count',
                    'timestamp',
                    'is_own_post',
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
