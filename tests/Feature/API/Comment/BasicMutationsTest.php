<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(2)->hasPosts(3)->hasComments(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
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

test('Should successfully comment on a post', function() {
    $slug = User::find(2)->posts()->first()->slug;
    
    $this->response
        ->postJson("/api/comments?user=$slug", [
            'body' => 'Hello World'
        ])
        ->assertCreated()
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

test('Should successfully update a comment', function() {
    $slug = $this->user->comments()->first()->slug;

    $this->response
        ->putJson("/api/comments/$slug", [
            'body' => 'Hello World'
        ])
        ->assertStatus(200)
        ->assertExactJson([
            'updated' => true,
            'message' => 'Comment successfully updated.',
        ]);

    $this->assertDatabaseHas('comments', [
        'body' => 'Hello World'
    ]);
});

test('Should not be able to update other user\'s comment', function() {
    $user = User::has('comments')->where('id', '!=', $this->user->id)->first();
    $slug = $user->comments()->first()->slug;
    
    $this->response
        ->putJson("/api/comments/$slug", [
            'body' => 'This comment has been edited'
        ])
        ->assertForbidden();
    
    $this->assertDatabaseMissing('comments', [
        'body' => 'This comment has been edited'
    ]);
});
