<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Cache};

beforeAll(function() {
    User::factory(2)->hasPosts(2)->hasComments(2)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('comments')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should successfully update a comment', function() {
    $comment = $this->user->comments()->first()->slug;

    $this->response
        ->putJson(route('comments.update', compact('comment')), [
            'body' => 'Hello World'
        ])
        ->assertOk();

    $this->assertTrue($this->user->comments()->whereBody('Hello World')->exists());
});

test('Should throw an error for attempting to update other user\'s comment', function() {
    $user = User::has('comments')->firstWhere('id', '!=', $this->user->id);
    $comment = $user->comments()->first()->slug;
    
    $this->response
        ->putJson(route('comments.update', compact('comment')), [
            'body' => 'This comment has been edited'
        ])
        ->assertForbidden();

    $this->assertTrue($user->comments()->whereBody('This comment has been edited')->doesntExist());
});
