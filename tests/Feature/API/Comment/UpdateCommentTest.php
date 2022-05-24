<?php

use App\Models\User;

beforeEach(function() {
    User::factory(2)->hasPosts(2)->hasComments(2)->create();

    $this->user = User::first();

    authenticate();
});

test('Should successfully update a comment', function() {
    $comment = $this->user->comments()->first();

    $this->putJson(
        route('comments.update', ['comment' => $comment->slug]),
        ['body' => 'Hello World']
    )->assertOk();

    $this->assertDatabaseMissing('comments', $comment->only('id', 'body'));
    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'body' => 'Hello World',
    ]);
});

test('Should throw an error for attempting to update other user\'s comment', function() {
    $user = User::has('comments')->firstWhere('id', '!=', $this->user->id);
    $comment = $user->comments()->first();
    
    $this->putJson(
        route('comments.update', ['comment' => $comment->slug]),
        ['body' => 'This comment has been edited']
    )->assertForbidden();

    $this->assertDatabaseHas('comments', $comment->only('id', 'body'));
    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
        'body' => 'This comment has been edited',
    ]);
});
