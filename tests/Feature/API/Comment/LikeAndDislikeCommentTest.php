<?php

use App\Models\{User, Comment, Notification as NotificationModel};
use App\Notifications\NotifyUponAction;
use Illuminate\Support\Facades\Notification;

beforeEach(function() {
    User::factory(2)->hasPosts(2)->hasComments(2)->create();

    $this->user = User::first();
    $this->comment = Comment::firstWhere('user_id', '!=', $this->user->id);

    authenticate();
});

test('Should be able to like a comment', function() {
    Notification::fake();

    $this->postJson(
        route('comments.like', ['comment' => $this->comment->slug])
    )->assertOk();

    Notification::assertSentTo(
        $this->comment->user,
        NotifyUponAction::class,
        fn ($notification) => (
            $notification->action === NotificationModel::LIKED_COMMENT
        )
    );

    $this->assertDatabaseHas('likables', [
        'user_id' => $this->user->id,
        'likable_id' => $this->comment->id,
        'likable_type' => Comment::class,
    ]);
});

test('Should not be able to like a comment that has already been liked', function() {
    Notification::fake();

    $this->user->likedComments()->attach($this->comment);

    $this->postJson(
        route('comments.like', ['comment' => $this->comment->slug])
    )->assertForbidden();

    Notification::assertNothingSent();

    $this->assertDatabaseCount('likables', 1);
});

test('Should be able to dislike a comment', function() {
    $data = [
        'user_id' => $this->user->id,
        'likable_id' => $this->comment->id,
        'likable_type' => Comment::class,
    ];

    $this->user->likedComments()->attach($this->comment);

    $this->assertDatabaseHas('likables', $data);

    $this->deleteJson(
        route('comments.dislike', ['comment' => $this->comment->slug])
    )->assertOk();

    $this->assertDatabaseMissing('likables', $data);
});

test('Should not be able to dislike a comment that is not liked', function() {
    $this->deleteJson(
        route('comments.dislike', ['comment' => $this->comment->slug])
    )->assertForbidden();

    $this->assertDatabaseMissing('likables', [
        'user_id' => $this->user->id,
        'likable_id' => $this->comment->id,
        'likable_type' => Comment::class,
    ]);
});
