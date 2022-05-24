<?php

use App\Models\User;

beforeEach(function() {
    User::factory(2)->hasPosts(3)->create();

    $this->user = User::first();

    authenticate();
});

test('Should successfully delete a post', function() {
    $post = $this->user->posts()->first()->slug;

    $this->deleteJson(route('posts.destroy', compact('post')))
        ->assertOk();

    $this->assertDatabaseMissing('posts', [
        'user_id' => $this->user->id,
        'slug' => $post,
    ]);
});

test('Should not be able to delete other user\'s post', function() {
    $user = User::firstWhere('id', '!=', $this->user->id);
    $post = $user->posts()->first()->slug;

    $this->deleteJson(route('posts.destroy', compact('post')))
        ->assertForbidden();

    $this->assertDatabaseHas('posts', [
        'user_id' => $user->id,
        'slug' => $post,
    ]);
});
