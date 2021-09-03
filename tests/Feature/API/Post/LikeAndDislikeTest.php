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

test('Can like a post', function() {
    $post = Post::find(10);

    $this->response
        ->postJson("/api/posts/$post->slug/like")
        ->assertOk()
        ->assertExactJson([
            'liked' => true,
            'message' => 'Post successfully liked.',
        ]);

    $this->assertDatabaseHas('likes', [
        'user_id' => $this->user->id,
        'post_id' => $post->id
    ]);
});

test('Can\'t like a post more than once', function() {
    $post = Post::find(10);

    $this->response
        ->postJson("/api/posts/$post->slug/like")
        ->assertOk()
        ->assertExactJson([
            'liked' => true,
            'message' => 'Post successfully liked.',
        ]);

    $this->response
        ->postJson("/api/posts/$post->slug/like")
        ->assertForbidden();

    $this->assertDatabaseCount('likes', 1);
});

test('Can dislike a post', function() {
    $post = Post::find(10);

    $this->user->likes()->attach($post->id);

    $this->response
        ->deleteJson("/api/posts/$post->slug/dislike")
        ->assertOk()
        ->assertExactJson([
            'disliked' => true,
            'message' => 'Post successfully disliked.',
        ]);

    $this->assertDatabaseMissing('likes', [
        'user_id' => $this->user->id,
        'post_id' => $post->id
    ]);
});

test('Can\'t dislike a post more than once', function() {
    $post = Post::find(10);

    $this->user->likes()->attach($post->id);

    $this->response
        ->deleteJson("/api/posts/$post->slug/dislike")
        ->assertOk()
        ->assertExactJson([
            'disliked' => true,
            'message' => 'Post successfully disliked.',
        ]);

    $this->response
        ->deleteJson("/api/posts/$post->slug/dislike")
        ->assertForbidden();

    $this->assertDatabaseMissing('likes', [
        'user_id' => $this->user->id,
        'post_id' => $post->id
    ]);
});
