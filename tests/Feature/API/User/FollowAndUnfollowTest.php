<?php

use App\Models\{User, Notification as NotificationModel};
use Illuminate\Support\Facades\{DB, Notification, Cache};
use App\Notifications\NotifyUponAction;

beforeAll(function() {
    User::factory(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('notifications')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should successfully follow a user', function() {
    $userToFollow = User::firstWhere('id', '!=', $this->user->id);

    Notification::fake();
    
    $this->response
        ->postJson(route('users.follow', ['user' => $userToFollow->slug]))
        ->assertOk();

    Notification::assertSentTo(
        $userToFollow,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->action === NotificationModel::FOLLOWED
        )
    );

    $this->assertTrue($this->user->following()->whereKey($userToFollow->id)->exists());
    $this->assertTrue($userToFollow->followers()->whereKey($this->user->id)->exists());
});

test('Should throw an error for following a user that has been already followed', function() {
    // Suppose the auth user already follows another user with the ID of 2 based on the test above.
    $userToFollow = User::firstWhere('id', '!=', $this->user->id);

    Notification::fake();

    $this->response
        ->postJson(route('users.follow', ['user' => $userToFollow->slug]))
        ->assertForbidden();

    Notification::assertNothingSent();
    
    $this->assertTrue($this->user->following()->whereKey($userToFollow->id)->count() === 1);
    $this->assertTrue($userToFollow->followers()->whereKey($this->user->id)->count() === 1);
});

test('Should successfully unfollow a user', function() {
    // Suppose the auth user already follows another user with the ID of 2 based on the test above.
    $userToUnfollow = User::firstWhere('id', '!=', $this->user->id);
    
    $this->response
        ->deleteJson(route('users.unfollow', ['user' => $userToUnfollow->slug]))
        ->assertOk();

    $this->assertTrue($this->user->following()->whereKey($userToUnfollow->id)->doesntExist());
    $this->assertTrue($userToUnfollow->followers()->whereKey($this->user->id)->doesntExist());
});

test('Should throw an error for unfollowing a user that is not followed', function() {
    $userToUnfollow = User::firstWhere('id', '!=', $this->user->id);

    $this->response
        ->deleteJson(route('users.unfollow', ['user' => $userToUnfollow->slug]))
        ->assertForbidden();

    $this->assertTrue($this->user->following()->whereKey($userToUnfollow->id)->doesntExist());
    $this->assertTrue($userToUnfollow->followers()->whereKey($this->user->id)->doesntExist());
});