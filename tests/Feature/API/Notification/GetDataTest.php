<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(3)->hasNotifications(4)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('notifications')->truncate();
});

test('Should return a paginated list of notifications', function() {
    $this->response
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonCount(4, 'items')
        ->assertJsonStructure([
            'items' => [
                '*' => [
                    'slug',
                    'user' => ['name', 'gender', 'image_url'],
                    'message',
                    'url',
                    'is_read',
                ]
            ],
            'has_more',
            'next_offset',
        ]);
});
