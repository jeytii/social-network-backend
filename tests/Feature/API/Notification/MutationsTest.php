<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Notifications\NotifyUponAction;

beforeAll(function() {
    User::factory(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('notifications')->truncate();
});

test('Should successfully peek at new notifications', function() {
    $user = User::firstWhere('id', '!=', $this->user->id);
    $inserts = collect(range(1, 4))->map(fn($action) => [
        'id' => Str::uuid(),
        'type' => get_class(new NotifyUponAction($user, $action)),
        'notifiable_type' => get_class(new User),
        'notifiable_id' => $this->user->id,
        'data' => json_encode([
            'user' => $user->only(['name', 'username', 'gender', 'image_url']),
            'action' => $action,
        ]),
    ])->toArray();

    DB::table('notifications')->insert($inserts);

    $this->response
        ->putJson(route('notifications.peek'))
        ->assertOk();

    $this->assertTrue($this->user->notifications()->where('peeked_at', null)->count() === 0);
});

test('Should successfully marked a specific notification as read', function() {
    $notificationId = $this->user->notifications()->first()->id;

    $this->response
        ->putJson(route('notifications.read', ['id' => $notificationId]))
        ->assertOk();

    $this->assertTrue($this->user->notifications()->where('read_at', '!=', null)->count() === 1);
});

test('Should successfully marked all notifications as read', function() {
    $this->response
        ->putJson(route('notifications.read.all'))
        ->assertOk();

    $this->assertTrue($this->user->unreadNotifications()->count() === 0);
});
