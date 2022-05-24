<?php

use App\Models\User;

beforeEach(function() {
    User::factory(3)->hasNotifications(3)->create();

    $this->user = User::first();

    authenticate();
});

test('Should successfully peek at new notifications', function() {
    $this->putJson(route('notifications.peek'))->assertOk();

    $this->assertTrue($this->user->notifications()->unpeeked()->count() === 0);
});

test('Should successfully mark a specific notification as read', function() {
    $notification = $this->user->notifications()->first()->slug;

    $this->putJson(route('notifications.read', compact('notification')))
        ->assertOk();

    $this->assertTrue($this->user->readNotifications()->count() === 1);
});

test('Should successfully marked all notifications as read', function() {
    $this->putJson(route('notifications.read.all'))->assertOk();

    $this->assertTrue($this->user->unreadNotifications()->count() === 0);
});
