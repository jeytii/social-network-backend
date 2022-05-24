<?php

use App\Models\{User, Post, Notification as NotificationModel};
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifyUponAction;

beforeEach(function() {
    User::factory(2)->hasPosts(2)->create();

    $this->user = User::first();
    $this->post = Post::firstWhere('user_id', '!=', $this->user->id);

    authenticate();
});

test('Should be able to like a post', function() {
    Notification::fake();

    $this->postJson(route('posts.like', ['post' => $this->post->slug]))
        ->assertOk();

    Notification::assertSentTo(
        $this->post->user,
        NotifyUponAction::class,
        fn ($notification) => (
            $notification->action === NotificationModel::LIKED_POST
        )
    );

    $this->assertDatabaseHas('likables', [
        'user_id' => $this->user->id,
        'likable_id' => $this->post->id,
        'likable_type' => Post::class,
    ]);
});

test('Should not be able to like a post that has already been liked', function() {
    Notification::fake();

    $this->user->likedPosts()->attach($this->post);

    $this->postJson(route('posts.like', ['post' => $this->post->slug]))
        ->assertForbidden();

    Notification::assertNothingSent();

    $this->assertDatabaseCount('likables', 1);
});

test('Should be able to dislike a post', function() {
    $this->user->likedPosts()->attach($this->post);

    $this->deleteJson(route('posts.dislike', ['post' => $this->post->slug]))
        ->assertOk();

    $this->assertDatabaseMissing('likables', [
        'user_id' => $this->user->id,
        'likable_id' => $this->post->id,
        'likable_type' => Post::class,
    ]);
});

test('Should not be able to dislike a post that is not liked', function() {
    $this->deleteJson(route('posts.dislike', ['post' => $this->post->slug]))
        ->assertForbidden();

    $this->assertDatabaseMissing('likables', [
        'user_id' => $this->user->id,
        'likable_id' => $this->post->id,
        'likable_type' => Post::class,
    ]);
});
