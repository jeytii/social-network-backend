<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

beforeEach(function() {
    $this->user = User::factory()->create();
    $this->response = $this->actingAs($this->user);

    Sanctum::actingAs($this->user, ['*']);
});

afterEach(function() {
    DB::table('users')->truncate();
});

test('Can follow a user', function() {
    $userToFollow = User::factory()->create();

    $this->response
        ->postJson("/api/users/follow/{$userToFollow->slug}")
        ->assertOk()
        ->assertJson(['followed' => true]);

    $this->assertTrue((bool) $this->user->following()->find($userToFollow->id));
    $this->assertTrue((bool) $userToFollow->followers()->find($this->user->id));
});

test('Can unfollow a user', function() {
    $userToUnfollow = User::factory()->create();

    // Assume that the auth user is already following another user.
    $this->user->following()->sync([$userToUnfollow->id]);
    
    $this->response
        ->deleteJson("/api/users/unfollow/{$userToUnfollow->slug}")
        ->assertOk()
        ->assertJson(['unfollowed' => true]);

    $this->assertFalse((bool) $this->user->following()->find($userToUnfollow->id));
    $this->assertFalse((bool) $userToUnfollow->followers()->find($this->user->id));
});

test('Can\'t unfollow a user that\'s not included in the list of followed users', function() {
    $userToUnfollow = User::factory()->create();

    $this->response
        ->deleteJson("/api/users/unfollow/{$userToUnfollow->slug}")
        ->assertForbidden();

    $this->assertFalse((bool) $this->user->following()->find($userToUnfollow->id));
    $this->assertFalse((bool) $userToUnfollow->followers()->find($this->user->id));
});

test('Can\'t follow a user that\'s already followed', function() {
    $userToFollow = User::factory()->create();

    // Assume that the auth user is already following another user.
    $this->user->following()->sync([$userToFollow->id]);

    $this->response
        ->postJson("/api/users/follow/{$userToFollow->slug}")
        ->assertForbidden();

    $this->assertTrue($this->user->following()->where('id', 2)->count() === 1);
    $this->assertTrue($userToFollow->followers()->where('id', 1)->count() === 1);
});

test('Should throw an error if the type of connection is not set', function() {
    $this->response
        ->getJson('/api/users/connections?page=1')
        ->assertNotFound();
});

test('Should throw an error if the type is neither "followers" nor "following"', function() {
    $this->response
        ->getJson('/api/users/connections?type=unknown&page=1')
        ->assertNotFound();
});

test('Should return the paginated list of followed users', function() {
    User::factory(40)->create();

    $this->user->following()->sync(range(2, 41));

    // First full-page scroll
    $this->response
        ->getJson('/api/users/connections?type=following&page=1')
        ->assertOk()
        ->assertJsonCount(20, 'data');

    // Second full-page scroll
    $this->response
        ->getJson('/api/users/connections?type=following&page=2')
        ->assertOk()
        ->assertJsonCount(20, 'data');

    // The last full-page scroll that returns data
    $this->response
        ->getJson('/api/users/connections?type=following&page=3')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('Should return the paginated list of followers', function() {
    User::factory(40)->create();

    $this->user->followers()->sync(range(2, 41));

    // First full-page scroll
    $this->response
        ->getJson('/api/users/connections?type=followers&page=1')
        ->assertOk()
        ->assertJsonCount(20, 'data');

    // Second full-page scroll
    $this->response
        ->getJson('/api/users/connections?type=followers&page=2')
        ->assertOk()
        ->assertJsonCount(20, 'data');

    // The last full-page scroll that returns data
    $this->response
        ->getJson('/api/users/connections?type=followers&page=3')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('Should return an error if the visited user profile doesn\'t exist', function() {
    $this->response
        ->getJson('/api/users/foobar/profile')
        ->assertNotFound();
});