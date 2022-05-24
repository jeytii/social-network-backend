<?php

use App\Models\{User, Post};

beforeEach(function() {
    User::factory(2)->hasPosts(2)->create();

    $this->user = User::first();
    $this->post = Post::doesntHave('bookmarkers')->first();

    authenticate();
});

test('Should be able to bookmark a post', function() {
    $this->postJson(route('posts.bookmark', ['post' => $this->post->slug]))
        ->assertOk();

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $this->post->id,
    ]);
});

test('Should not be able to bookmark a post that has already been bookmarked', function() {
    $this->user->bookmarks()->attach($this->post);

    $this->postJson(route('posts.bookmark', ['post' => $this->post->slug]))
        ->assertForbidden();

    $this->assertDatabaseCount('bookmarks', 1);
});

test('Should be able to unbookmark a post', function() {
    $this->user->bookmarks()->attach($this->post);
    
    $this->deleteJson(route('posts.unbookmark', ['post' => $this->post->slug]))
        ->assertOk();

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $this->post->id,
    ]);
});

test('Should not be able to unbookmark a post that is not bookmarked', function() {
    $this->deleteJson(route('posts.unbookmark', ['post' => $this->post->slug]))
        ->assertForbidden();

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $this->post->id,
    ]);
});
