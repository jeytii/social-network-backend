<?php

use App\Models\User;

beforeEach(function() {
    User::factory(2)->hasPosts(2)->hasComments(2)->create();

    $this->user = User::first();

    authenticate();
});

test('Should successfully delete a comment', function() {
    $comment = $this->user->comments()->first();
    $data = [
        'id' => $comment->id,
        'user_id' => $this->user->id,
    ];

    $this->assertDatabaseHas('comments', $data);

    $this->deleteJson(route('comments.destroy', ['comment' => $comment->slug]))
        ->assertOk();

    $this->assertDatabaseMissing('comments', $data);
});

test('Should not be able to delete other user\'s comment', function() {
    $user = User::has('comments')->firstWhere('id', '!=', $this->user->id);
    $comment = $user->comments()->first();
    
    $this->deleteJson(route('comments.destroy', ['comment' => $comment->slug]))
        ->assertForbidden();

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'user_id' => $user->id,
    ]);
});
