<?php

use App\Models\User;

beforeEach(function() {
    User::factory(3)->hasNotifications(4)->create();

    authenticate();
});

test('Should return a paginated list of notifications', function() {
    $this->getJson(route('notifications.index'))
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
