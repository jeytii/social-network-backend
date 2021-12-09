<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Notifications\NotifyUponAction;

beforeAll(function() {
    User::factory(3)->create();

    $user = User::firstWhere('id', '!=', User::first()->id);
    $inserts = collect(range(1, 4))->map(fn($action) => [
        'id' => Str::uuid(),
        'slug' => uniqid(),
        'type' => get_class(new NotifyUponAction($user, $action, '/sample/path')),
        'notifiable_type' => get_class(new User),
        'notifiable_id' => User::first()->id,
        'data' => json_encode([
            'user' => $user->only(['name', 'gender', 'image_url']),
            'action' => $action,
            'path' => '/sample/path',
        ]),
    ])->toArray();

    DB::table('notifications')->insert($inserts);
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
                    'action',
                    'path',
                    'is_read',
                ]
            ],
            'has_more',
            'next_offset',
            'status',
        ]);
});
