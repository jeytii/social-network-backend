<?php

use App\Models\User;

beforeEach(function() {
    User::factory(2)->hasPosts()->create();

    $this->user = User::first();

    authenticate();
});

test('Should successfully update a post', function() {
    $post = $this->user->posts()->first();

    $this->putJson(
        route('posts.update', ['post' => $post->slug]),
        ['body' => 'Hello World']
    )->assertOk();
    
    $this->assertDatabaseMissing('posts', $post->only('id', 'body'));
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'body' => 'Hello World',
    ]);
});

test('Should not be able to update other user\'s post', function() {
    $user = User::firstWhere('id', '!=', $this->user->id);
    $post = $user->posts()->first();

    $this->putJson(
        route('posts.update', ['post' => $post->slug]),
        ['body' => 'Hello World']
    )->assertForbidden();

    $this->assertDatabaseHas('posts', $post->only('id', 'body'));
    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
        'body' => 'Hello World',
    ]);
});
