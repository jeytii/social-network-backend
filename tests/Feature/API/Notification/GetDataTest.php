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
        'type' => get_class(new NotifyUponAction($user, $action)),
        'notifiable_type' => get_class(new User),
        'notifiable_id' => User::first()->id,
        'data' => json_encode([
            'user' => $user->only(config('api.response.user.basic')),
            'action' => $action,
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
        ->getJson(route('notifications.get'))
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['data', 'read_at']
            ],
            'has_more',
            'next_offset',
            'status',
            'message',
        ]);
});
