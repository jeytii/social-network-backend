<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(3)->hasPosts(5)->create();
});

beforeEach(function() {
    $this->post = DB::table('posts')->find(10);
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Can like a post', function() {
    $this->response
        ->postJson("/api/posts/{$this->post->slug}/like")
        ->assertOk()
        ->assertExactJson([
            'liked' => true,
            'message' => 'Post successfully liked.',
        ]);

    $this->assertDatabaseHas('likes', [
        'user_id' => $this->user->id,
        'post_id' => $this->post->id
    ]);
});

test('Can\'t like a post more than once', function() {
    // Suppose the user has already liked the selected post based from the test above.
    $this->response
        ->postJson("/api/posts/{$this->post->slug}/like")
        ->assertForbidden();

    $this->assertDatabaseCount('likes', 1);
});

test('Can dislike a post', function() {
    $this->user->likes()->attach($this->post->id);

    $this->response
        ->deleteJson("/api/posts/{$this->post->slug}/dislike")
        ->assertOk()
        ->assertExactJson([
            'disliked' => true,
            'message' => 'Post successfully disliked.',
        ]);

    $this->assertDatabaseMissing('likes', [
        'user_id' => $this->user->id,
        'post_id' => $this->post->id
    ]);
});

test('Can\'t dislike a post more than once', function() {
    $this->user->likes()->attach($this->post->id);

    $this->response
        ->deleteJson("/api/posts/{$this->post->slug}/dislike")
        ->assertOk()
        ->assertExactJson([
            'disliked' => true,
            'message' => 'Post successfully disliked.',
        ]);

    $this->response
        ->deleteJson("/api/posts/{$this->post->slug}/dislike")
        ->assertForbidden();

    $this->assertDatabaseMissing('likes', [
        'user_id' => $this->user->id,
        'post_id' => $this->post->id
    ]);
});
