<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Cache};

beforeAll(function() {
    User::factory(3)->hasNotifications(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('notifications')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should successfully peek at new notifications', function() {
    $this->response
        ->putJson(route('notifications.peek'))
        ->assertOk();

    $this->assertTrue($this->user->notifications()->unpeeked()->count() === 0);
});

test('Should successfully mark a specific notification as read', function() {
    $notification = $this->user->notifications()->first()->slug;

    $this->response
        ->putJson(route('notifications.read', compact('notification')))
        ->assertOk();

    $this->assertTrue($this->user->readNotifications()->count() === 1);
});

test('Should successfully marked all notifications as read', function() {
    $this->response
        ->putJson(route('notifications.read.all'))
        ->assertOk();

    $this->assertTrue($this->user->unreadNotifications()->count() === 0);
});
