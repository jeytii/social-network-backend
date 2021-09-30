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

test('Should throw an error if the comment body is blank', function () {
    Notification::fake();

    $this->response
        ->postJson(route('comments.store'))
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Comment should not be blank.']);

    Notification::assertNothingSent();
});

test('Should throw an error if the body length is greater than maximum', function () {
    Notification::fake();

    $maxLength = config('api.max_lengths.long_text');

    $this->response
        ->postJson(route('comments.store'), [
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ["Maximum character length is {$maxLength}."]);

    Notification::assertNothingSent();
});

test('Should throw an error if post doesn\'t exist', function() {
    $this->response
        ->postJson(route('comments.store'), [
            'pid' => 123456,
            'body' => 'Hello World',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.pid', ['Post does not exist.']);
});

test('Should successfully create a comment', function() {
    Notification::fake();

    $user = User::has('posts')->firstWhere('id', '!=', $this->user->id);
    $slug = $user->posts()->first()->slug;
    
    $this->response
        ->postJson(route('comments.store'), [
            'pid' => $slug,
            'body' => 'Hello World',
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'slug',
                'body',
                'timestamp',
                'is_own_comment',
                'is_edited',
                'user' => array_merge(config('api.response.user.basic'), ['slug'])
            ]
        ]);

    Notification::assertSentTo(
        $user,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->action === config('api.notifications.commented_on_post')
        )
    );
});

test('Should notify the mentioned users along with OP upon commenting', function() {
    Notification::fake();

    $user = User::has('posts')->firstWhere('id', '!=', $this->user->id);
    $slug = $user->posts()->first()->slug;
    $exceptionIds = [$this->user->id, $user->id];
    $usernames = User::whereNotIn('id', $exceptionIds)->limit(3)->pluck('username');
    
    $this->response
        ->postJson(route('comments.store'), [
            'pid' => $slug,
            'body' => "Shoutout to @{$usernames[0]}, @{$usernames[1]}, and @{$usernames[2]}"
        ])
        ->assertCreated();

    Notification::assertTimesSent(4, NotifyUponAction::class);
});

test('Should successfully update a comment', function() {
    $comment = $this->user->comments()->first();

    $this->response
        ->putJson(route('comments.update', ['comment' => $comment->slug]), [
            'body' => 'Hello World'
        ])
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully updated a comment.',
        ]);

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'body' => 'Hello World',
    ]);
});

test('Should throw an error for attempting to update other user\'s comment', function() {
    $user = User::has('comments')->firstWhere('id', '!=', $this->user->id);
    $comment = $user->comments()->first();
    
    $this->response
        ->putJson(route('comments.update', ['comment' => $comment->slug]), [
            'body' => 'This comment has been edited'
        ])
        ->assertForbidden();
    
    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
        'body' => 'This comment has been edited',
    ]);
});

test('Should successfully delete a comment', function() {
    $slug = $this->user->comments()->first()->slug;

    $this->response
        ->deleteJson(route('comments.destroy', ['comment' => $slug]))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully deleted a comment.',
        ]);

    $this->assertDatabaseMissing('comments', compact('slug'));
});

test('Should not be able to delete other user\'s comment', function() {
    $user = User::has('comments')->firstWhere('id', '!=', $this->user->id);
    $slug = $user->comments()->first()->slug;
    
    $this->response
        ->deleteJson(route('comments.destroy', ['comment' => $slug]))
        ->assertForbidden();
    
    $this->assertDatabaseHas('comments', compact('slug'));
});
