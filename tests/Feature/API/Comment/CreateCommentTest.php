<?php

use App\Models\{User, Notification as NotificationModel};
use Illuminate\Support\Facades\{DB, Notification, Cache};
use App\Notifications\NotifyUponAction;

beforeAll(function() {
    User::factory(2)->hasPosts(2)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('comments')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should throw an error if the body length is greater than maximum', function () {
    Notification::fake();

    $this->response
        ->postJson(route('comments.store'), [
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['The number of characters exceeds the maximum length.']);

    Notification::assertNothingSent();
});

test('Should throw an error if post doesn\'t exist', function() {
    $this->response
        ->postJson(route('comments.store'), [
            'post' => 123456,
            'body' => 'Hello World',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.post', ['Post does not exist.']);
});

test('Should successfully create a comment', function() {
    $user = User::has('posts')->firstWhere('id', '!=', $this->user->id);
    $slug = $user->posts()->first()->slug;

    Notification::fake();
    
    $this->response
        ->postJson(route('comments.store'), [
            'post' => $slug,
            'body' => 'Hello World',
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'status',
            'data' => [
                'slug',
                'post_slug',
                'body',
                'timestamp',
                'is_own_comment',
                'is_edited',
                'user' => config('api.response.user.basic')
            ]
        ]);

    Notification::assertSentTo(
        $user,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->action === NotificationModel::COMMENTED_ON_POST
        )
    );
});
