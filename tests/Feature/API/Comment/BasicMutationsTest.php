<?php

use App\Models\User;
use App\Notifications\NotifyUponAction;
use Illuminate\Support\Facades\{DB, Notification};

beforeAll(function() {
    User::factory(6)->hasPosts(3)->hasComments(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should not create a comment if body is blank or not set', function () {
    Notification::fake();

    $this->response
        ->postJson('/api/comments')
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Comment should not be blank.']);

    Notification::assertNothingSent();
});

test('Should not create a comment if body length is greater than maximum length', function () {
    Notification::fake();

    $this->response
        ->postJson('/api/comments', [
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Maximum character length is 180.']);

    Notification::assertNothingSent();
});

test('Should successfully comment on a post', function() {
    Notification::fake();

    $user = User::has('posts')->where('id', '!=', $this->user->id)->first();
    $slug = $user->posts()->first()->slug;
    
    $this->response
        ->postJson("/api/comments?uid={$slug}", [
            'body' => 'Hello World'
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'comment' => [
                    'slug',
                    'body',
                    'timestamp',
                    'is_own_comment',
                    'is_edited',
                    'user' => array_merge(config('api.response.user.basic'), ['slug'])
                ],
            ]
        ]);

    Notification::assertSentTo(
        $user,
        NotifyUponAction::class,
        fn($notification, $channels, $notifiable) => (
            $notification->user->id === $this->user->id &&
            $notification->actionType === config('api.notifications.commented_on_post') &&
            $notifiable->id === $user->id
        )
    );
});

test('Should notify the mentioned users along with OP upon commenting', function() {
    Notification::fake();

    $user = User::has('posts')->where('id', '!=', $this->user->id)->first();
    $slug = $user->posts()->first()->slug;
    $exceptIds = [$this->user->id, $user->id];
    $usernames = User::whereNotIn('id', $exceptIds)->limit(3)->pluck('username');
    
    $this->response
        ->postJson("/api/comments?uid={$slug}", [
            'body' => "Shoutout to @{$usernames[0]}, @{$usernames[1]}, and @{$usernames[2]}"
        ])
        ->assertCreated();

    Notification::assertTimesSent(4, NotifyUponAction::class);
});

test('Should successfully update a comment', function() {
    $slug = $this->user->comments()->first()->slug;

    $this->response
        ->putJson("/api/comments/{$slug}", [
            'body' => 'Hello World'
        ])
        ->assertOk()
        ->assertExactJson([
            'updated' => true,
            'message' => 'Comment successfully updated.',
        ]);

    $this->assertDatabaseHas('comments', [
        'body' => 'Hello World'
    ]);
});

test('Should not be able to update other user\'s comment', function() {
    $user = User::has('comments')->where('id', '!=', $this->user->id)->first();
    $slug = $user->comments()->first()->slug;
    
    $this->response
        ->putJson("/api/comments/{$slug}", [
            'body' => 'This comment has been edited'
        ])
        ->assertForbidden();
    
    $this->assertDatabaseMissing('comments', [
        'body' => 'This comment has been edited'
    ]);
});

test('Should successfully delete a comment', function() {
    $slug = $this->user->comments()->first()->slug;

    $this->response
        ->deleteJson("/api/comments/{$slug}")
        ->assertOk()
        ->assertExactJson([
            'deleted' => true,
            'message' => 'Comment successfully deleted.',
        ]);

    $this->assertDatabaseMissing('comments', compact('slug'));
});

test('Should not be able to delete other user\'s comment', function() {
    $user = User::has('comments')->where('id', '!=', $this->user->id)->first();
    $slug = $user->comments()->first()->slug;
    
    $this->response
        ->deleteJson("/api/comments/{$slug}")
        ->assertForbidden();
    
    $this->assertDatabaseHas('comments', compact('slug'));
});
