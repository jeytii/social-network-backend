<?php

use App\Models\{User, Post};
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function() {
    User::factory(3)->hasPosts(5)->create();

    $this->user = User::first();
    $this->response = $this->actingAs($this->user);

    Sanctum::actingAs($this->user, ['*']);
});

afterEach(function() {
    DB::table('users')->truncate();
});

test('Can bookmark a post', function() {
    $post = Post::find(10);

    $this->response
        ->postJson("/api/posts/$post->slug/bookmark")
        ->assertOk()
        ->assertExactJson([
            'bookmarked' => true,
            'message' => 'Post successfully bookmarked.',
        ]);

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $post->id
    ]);
});

test('Can\'t bookmark a post more than once', function() {
    $post = Post::find(10);

    $this->response
        ->postJson("/api/posts/$post->slug/bookmark")
        ->assertOk()
        ->assertExactJson([
            'bookmarked' => true,
            'message' => 'Post successfully bookmarked.',
        ]);

    $this->response
        ->postJson("/api/posts/$post->slug/bookmark")
        ->assertForbidden();

    $this->assertDatabaseCount('bookmarks', 1);
});

test('Can unbookmark a post', function() {
    $post = Post::find(10);

    $this->user->bookmarks()->attach($post->id);

    $this->response
        ->deleteJson("/api/posts/$post->slug/unbookmark")
        ->assertOk()
        ->assertExactJson([
            'unbookmarked' => true,
            'message' => 'Post successfully unbookmarked.',
        ]);

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $post->id
    ]);
});

test('Can\'t unbookmark a post more than once', function() {
    $post = Post::find(10);

    $this->user->bookmarks()->attach($post->id);

    $this->response
        ->deleteJson("/api/posts/$post->slug/unbookmark")
        ->assertOk()
        ->assertExactJson([
            'unbookmarked' => true,
            'message' => 'Post successfully unbookmarked.',
        ]);

    $this->response
        ->deleteJson("/api/posts/$post->slug/unbookmark")
        ->assertForbidden();

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $post->id
    ]);
});
