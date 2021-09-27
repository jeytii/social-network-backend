<?php

use App\Models\{User, Post};
use App\Notifications\NotifyUponAction;
use Illuminate\Support\Facades\{DB, Notification};

beforeAll(function() {
    User::factory(3)->hasPosts(5)->create();
});

beforeEach(function() {
    $this->post = Post::find(10);
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
});

test('Can like a post', function() {
    Notification::fake();

    $this->response
        ->postJson("/api/posts/{$this->post->slug}/like")
        ->assertOk()
        ->assertExactJson([
            'liked' => true,
            'message' => 'Post successfully liked.',
        ]);

    Notification::assertSentTo(
        $this->post->user,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->user->id === $this->user->id &&
            $notification->actionType === config('api.notifications.post_liked')
        )
    );

    $this->assertDatabaseHas('likes', [
        'user_id' => $this->user->id,
        'post_id' => $this->post->id
    ]);
});

test('Can\'t like a post more than once', function() {
    Notification::fake();

    // Suppose the user has already liked the selected post based from the test above.
    $this->response
        ->postJson("/api/posts/{$this->post->slug}/like")
        ->assertForbidden();

    Notification::assertNothingSent();

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
