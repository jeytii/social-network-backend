<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

trait UsersTestTrait {
    use RefreshDatabase, WithFaker;

    /**
     * Seeds 100 fake user models.
     * See database/seeders/DatabaseSeeder.php
     *
     * @var boolean
     */
    protected $seed = true;
}

uses(UsersTestTrait::class);

beforeEach(function() {
    Sanctum::actingAs(User::first(), ['*']);
});

afterEach(function() {
    DB::table('users')->truncate();
});

test('Should return paginated list of users until no model is left', function() {
    $user = test()->actingAs(User::first());

    // First scroll full-page bottom
    $user->getJson('/api/users?page=1')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'data' => [
                '*' => ['slug', 'name', 'username', 'gender', 'image_url'],
            ],
        ]);

    // Second scroll full-page bottom
    $user->getJson('/api/users?page=2')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 3);

    // The last full-page scroll that returns data
    $user->getJson('/api/users?page=5')
        ->assertOk()
        ->assertJsonCount(19, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    // Full-page scroll attempt but should return empty list
    $user->getJson('/api/users?page=6')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should successfully return 3 suggested users', function() {
    test()->actingAs(User::first())
        ->getJson('/api/users/suggested')
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['slug', 'name', 'username', 'gender', 'image_url'],
            ],
        ]);
});

test('Should throw validation errors if input values are incorrect in update profile form', function() {
    test()->actingAs(User::first())
        ->putJson('/api/users/auth/update', [
            'birth_day' => 32,
            'bio' => $this->faker->paragraphs(5, true)
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'name' => ['The name field is required.'],
                'birth_month' => ['The birth month field is required.'],
                'birth_day' => ['Birth day must be between 1 and 31 only.'],
                'birth_year' => ['The birth year field is required.'],
                'bio' => ['The number of characters exceeds the maximum length.'],
            ]
        ]);
});

test('Can\'t update the birth date that has been already set', function() {
    $user = User::factory()->create();

    test()->actingAs($user)
        ->putJson('/api/users/auth/update', [
            'name' => $user->name,
            'birth_month' => 'January',
            'birth_day' => 12,
            'birth_year' => 1996,
        ])
        ->assertOk();
    
    $updatedUser = User::find($user->id);

    test()->assertTrue($user->full_birth_date === $updatedUser->full_birth_date);
});

test('Can update the profile successfully', function() {
    $user = User::factory()->create([
        'birth_month' => null,
        'birth_day' => null,
        'birth_year' => null,
    ]);

    test()->actingAs($user)
        ->putJson('/api/users/auth/update', [
            'name' => 'John Doe',
            'birth_month' => 'December',
            'birth_day' => 10,
            'birth_year' => 1990,
            'location' => 'Philippines',
            'bio' => 'Hello World',
        ])
        ->assertOk();
    
    $updatedUser = User::find($user->id);

    test()->assertTrue($updatedUser->name === 'John Doe');
    test()->assertTrue($updatedUser->full_birth_date === 'December 10, 1990');
    test()->assertTrue($updatedUser->location === 'Philippines');
    test()->assertTrue($updatedUser->bio === 'Hello World');
});

test('Can follow a user', function() {
    $user = User::first();
    $userToFollow = User::find(2);

    test()->actingAs($user)
        ->postJson("/api/users/follow/{$userToFollow->slug}")
        ->assertOk()
        ->assertJson(['followed' => true]);

    test()->assertTrue((bool) $user->following()->find($userToFollow->id));
    test()->assertTrue((bool) $userToFollow->followers()->find($user->id));
});

test('Can unfollow a user', function() {
    $user = User::first();
    $userToUnfollow = User::find(2);

    // Assume that the auth user is already following another user.
    $user->following()->sync([$userToUnfollow->id]);
    
    test()->actingAs($user)
        ->deleteJson("/api/users/unfollow/{$userToUnfollow->slug}")
        ->assertOk()
        ->assertJson(['unfollowed' => true]);

    test()->assertFalse((bool) $user->following()->find($userToUnfollow->id));
    test()->assertFalse((bool) $userToUnfollow->followers()->find($user->id));
});

test('Can\'t unfollow a user that\'s not included in the list of followed users', function() {
    $user = User::first();
    $userToUnfollow = User::find(2);

    test()->actingAs($user)
        ->deleteJson("/api/users/unfollow/{$userToUnfollow->slug}")
        ->assertForbidden();

    test()->assertFalse((bool) $user->following()->find($userToUnfollow->id));
    test()->assertFalse((bool) $userToUnfollow->followers()->find($user->id));
});

test('Can\'t follow a user that\'s already followed', function() {
    $user = User::first();
    $userToFollow = User::find(2);

    // Assume that the auth user is already following another user.
    $user->following()->sync([$userToFollow->id]);

    test()->actingAs($user)
        ->postJson("/api/users/follow/{$userToFollow->slug}")
        ->assertForbidden();

    test()->assertTrue($user->following()->where('id', 2)->count() === 1);
    test()->assertTrue($userToFollow->followers()->where('id', 1)->count() === 1);
});

test('Should throw an error if the type of connection is not set', function() {
    test()->actingAs(User::first())
        ->getJson('/api/users/connections?page=1')
        ->assertNotFound();
});

test('Should throw an error if the type is neither "followers" nor "following"', function() {
    test()->actingAs(User::first())
        ->getJson('/api/users/connections?type=unknown&page=1')
        ->assertNotFound();
});

test('Should return the paginated list of followed users', function() {
    $user = User::first();
    $user->following()->sync(range(11, 50));
    
    $mockedUser = $this->actingAs($user);

    // First full-page scroll
    $mockedUser->getJson('/api/users/connections?type=following&page=1')
        ->assertOk()
        ->assertJsonCount(20, 'data');

    // Second full-page scroll
    $mockedUser->getJson('/api/users/connections?type=following&page=2')
        ->assertOk()
        ->assertJsonCount(20, 'data');

    // The last full-page scroll that returns data
    $mockedUser->getJson('/api/users/connections?type=following&page=3')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('Should return the paginated list of followers', function() {
    $user = User::find(2);
    $user->followers()->sync(range(31, 70));

    $mockedUser = $this->actingAs($user);

    // First full-page scroll
    $mockedUser->getJson('/api/users/connections?type=followers&page=1')
        ->assertOk()
        ->assertJsonCount(20, 'data');

    // Second full-page scroll
    $mockedUser->getJson('/api/users/connections?type=followers&page=2')
        ->assertOk()
        ->assertJsonCount(20, 'data');

    // The last full-page scroll that returns data
    $mockedUser->getJson('/api/users/connections?type=followers&page=3')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('Should return an error if the visited user profile doesn\'t exist', function() {
    test()->actingAs(User::first())
        ->get('/api/users/foobar/profile')
        ->assertNotFound();
});

test('The visited user profile is not self', function() {
    $visitedUsername = User::find(2)->username;

    test()->actingAs(User::first())
        ->get("/api/users/{$visitedUsername}/profile")
        ->assertOk()
        ->assertJsonPath('data.is_self', false);
});

test('The visited user profile is self', function() {
    $user = User::first();

    test()->actingAs($user)
        ->get("/api/users/{$user->username}/profile")
        ->assertOk()
        ->assertJsonPath('data.is_self', true);
});

test('Should return the profile data with the number of followers and followed users', function() {
    $user = User::first();

    // Assume that the auth user is already following 40 users.
    $user->following()->sync(range(61, 100));

    test()->actingAs($user)
        ->get("/api/users/{$user->username}/profile")
        ->assertOk()
        ->assertJsonPath('data.followers_count', 0)
        ->assertJsonPath('data.following_count', 40);
});
