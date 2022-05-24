<?php

use App\Models\{User, Notification as NotificationModel};
use App\Notifications\NotifyUponAction;
use Illuminate\Support\Facades\Notification;

beforeEach(function() {
    User::factory(3)->create();

    $this->user = User::first();
    $this->user2 = User::firstWhere('id', '!=', $this->user->id);
    
    authenticate();
});

test('Should successfully follow a user', function() {
    Notification::fake();
    
    $this->postJson(route('users.follow', ['user' => $this->user2->slug]))
        ->assertOk();

    Notification::assertSentTo(
        $this->user2,
        NotifyUponAction::class,
        fn ($notification) => (
            $notification->action === NotificationModel::FOLLOWED
        )
    );

    $this->assertDatabaseHas('connections', [
        'follower_id' => $this->user->id,
        'following_id' => $this->user2->id,
    ]);
});

test('Should throw an error for following a user that has been already followed', function() {
    $this->user->following()->attach($this->user2);

    Notification::fake();

    $this->postJson(route('users.follow', ['user' => $this->user2->slug]))
        ->assertForbidden();

    Notification::assertNothingSent();

    $this->assertDatabaseCount('connections', 1);
});

test('Should successfully unfollow a user', function() {
    $this->user->following()->attach($this->user2);
    
    $this->deleteJson(route('users.unfollow', ['user' => $this->user2->slug]))
        ->assertOk();

    $this->assertDatabaseMissing('connections', [
        'follower_id' => $this->user->id,
        'following_id' => $this->user2->id,
    ]);
});

test('Should throw an error for unfollowing a user that is not followed', function() {
    $this->deleteJson(route('users.unfollow', ['user' => $this->user2->slug]))
        ->assertForbidden();

    $this->assertDatabaseMissing('connections', [
        'follower_id' => $this->user->id,
        'following_id' => $this->user2->id,
    ]);
});