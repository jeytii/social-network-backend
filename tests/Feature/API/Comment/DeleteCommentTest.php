<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(2)->hasPosts(2)->hasComments(2)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('comments')->truncate();
});

test('Should successfully delete a comment', function() {
    $comment = $this->user->comments()->first()->slug;

    $this->response
        ->deleteJson(route('comments.destroy', compact('comment')))
        ->assertOk();

    $this->assertTrue($this->user->comments()->whereSlug($comment)->doesntExist());
});

test('Should not be able to delete other user\'s comment', function() {
    $user = User::has('comments')->firstWhere('id', '!=', $this->user->id);
    $comment = $user->comments()->first()->slug;
    
    $this->response
        ->deleteJson(route('comments.destroy', compact('comment')))
        ->assertForbidden();
    
    $this->assertTrue($user->comments()->whereSlug($comment)->exists());
});
