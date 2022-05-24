<?php

use App\Models\{User, Notification as NotificationModel};
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifyUponAction;

beforeEach(function() {
    User::factory(2)->hasPosts(2)->create();

    $this->user = User::first();

    authenticate();
});

test('Should throw an error if the body length is greater than maximum', function () {
    Notification::fake();
    $data = [
        'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
    ];

    $this->postJson(route('comments.store'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['The body must not be greater than 180 characters.']);

    Notification::assertNothingSent();
});

test('Should throw an error if post doesn\'t exist', function() {
    $data = [
        'post' => 123456,
        'body' => 'Hello World',
    ];

    $this->postJson(route('comments.store'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.post', ['The selected post is invalid.']);
});

test('Should successfully create a comment', function() {
    $user = User::has('posts')->firstWhere('id', '!=', $this->user->id);
    $post = $user->posts()->first();
    $data = [
        'post' => $post->slug,
        'body' => 'Hello World',
    ];

    Notification::fake();
    
    $this->postJson(route('comments.store'), $data)
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'slug',
                'post_slug',
                'body',
                'timestamp',
                'is_own_comment',
                'is_edited',
                'user' => config('response.user')
            ]
        ]);

    Notification::assertSentTo(
        $user,
        NotifyUponAction::class,
        fn ($notification) => (
            $notification->action === NotificationModel::COMMENTED_ON_POST
        )
    );

    $this->assertDatabaseHas('comments', [
        'user_id' => $this->user->id,
        'post_id' => $post->id,
        'body' => 'Hello World',
    ]);
});
