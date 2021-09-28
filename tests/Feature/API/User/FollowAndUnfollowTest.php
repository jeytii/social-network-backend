<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Notification};
use App\Notifications\NotifyUponAction;

beforeAll(function() {
    User::factory(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
});

test('Should successfully follow a user', function() {
    Notification::fake();

    $userToFollow = User::find(2);
    
    $this->response
        ->postJson(route('users.follow', ['user' => $userToFollow->slug]))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully followed user.',
        ]);

    $this->assertTrue((bool) $this->user->following()->find($userToFollow->id));
    $this->assertTrue((bool) $userToFollow->followers()->find($this->user->id));

    Notification::assertSentTo(
        $userToFollow,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->user->id === $this->user->id &&
            $notification->actionType === config('api.notifications.user_followed')
        )
    );
});

test('Should throw an error for following a user that has been already followed', function() {
    Notification::fake();

    // Suppose the auth user already follows another user with the ID of 2 based on the test above.
    $userToFollow = User::find(2);

    $this->response
        ->postJson(route('users.follow', ['user' => $userToFollow->slug]))
        ->assertForbidden();

    $this->assertTrue($this->user->following()->where('id', 2)->count() === 1);
    $this->assertTrue($userToFollow->followers()->where('id', 1)->count() === 1);

    Notification::assertNothingSent();
});

test('Should successfully unfollow a user', function() {
    // Suppose the auth user already follows another user with the ID of 2 based on the test above.
    $userToUnfollow = User::find(2);
    
    $this->response
        ->deleteJson(route('users.unfollow', ['user' => $userToUnfollow->slug]))
        ->assertOk()
        ->assertJson([
            'status' => 200,
            'message' => 'Successfully unfollowed user.',
        ]);

    $this->assertFalse((bool) $this->user->following()->find($userToUnfollow->id));
    $this->assertFalse((bool) $userToUnfollow->followers()->find($this->user->id));
});

test('Should throw an error for unfollowing a user that is not followed', function() {
    $userToUnfollow = User::find(2);

    $this->response
        ->deleteJson(route('users.unfollow', ['user' => $userToUnfollow->slug]))
        ->assertForbidden();

    $this->assertFalse((bool) $this->user->following()->find($userToUnfollow->id));
    $this->assertFalse((bool) $userToUnfollow->followers()->find($this->user->id));
});

// test('Should return the paginated list of followed users', function() {
//     $this->user->following()->sync(range(2, 21));

//     // First full-page scroll
//     $this->response
//         ->getJson("/api/profile/{$this->user->username}/following?page=1")
//         ->assertOk()
//         ->assertJsonCount(20, 'data')
//         ->assertJsonPath('has_more', false)
//         ->assertJsonPath('next_offset', null);

//     // The last full-page scroll that returns an empty list
//     $this->response
//         ->getJson("/api/profile/{$this->user->username}/following?page=2")
//         ->assertOk()
//         ->assertJsonCount(0, 'data')
//         ->assertJsonPath('has_more', false)
//         ->assertJsonPath('next_offset', null);
// });

// test('Should return the paginated list of followers', function() {
//     $this->user->followers()->sync(range(26, 45));

//     // First full-page scroll
//     $this->response
//         ->getJson("/api/profile/{$this->user->username}/followers?page=1")
//         ->assertOk()
//         ->assertJsonCount(20, 'data')
//         ->assertJsonPath('has_more', false)
//         ->assertJsonPath('next_offset', null);

//     // The last full-page scroll that returns an empty list
//     $this->response
//         ->getJson("/api/profile/{$this->user->username}/followers?page=2")
//         ->assertOk()
//         ->assertJsonCount(0, 'data')
//         ->assertJsonPath('has_more', false)
//         ->assertJsonPath('next_offset', null);
// });

// test('Should return an error if the visited user profile doesn\'t exist', function() {
//     $this->response
//         ->getJson('/api/profile/foobar')
//         ->assertNotFound();
// });