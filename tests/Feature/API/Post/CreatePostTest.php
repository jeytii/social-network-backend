<?php

use App\Models\User;

beforeEach(function() {
    User::factory()->create();

    authenticate();
});

test('Should throw an error if body\'s length is greater than maximum length', function () {
    $data = [
        'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
    ];

    $this->postJson(route('posts.store'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['The body must not be greater than 180 characters.']);
});

test('Should successfully create a post', function() {
    $this->postJson(route('posts.store'), ['body' => 'Sample post'])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'slug',
                'body',
                'likes_count',
                'comments_count',
                'timestamp',
                'is_own_post',
                'is_liked',
                'is_edited',
                'is_bookmarked',
                'user' => config('response.user')
            ],
        ]);

    $this->assertDatabaseHas('posts', [
        'user_id' => User::first()->id,
        'body' => 'Sample post',
    ]);
});
