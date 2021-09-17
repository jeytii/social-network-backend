<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;

uses(WithFaker::class);

beforeAll(function() {
    User::factory(60)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('The visited user profile is not self', function() {
    $username = DB::table('users')->find(3)->username;

    $this->response
        ->getJson("/api/profile/{$username}")
        ->assertOk()
        ->assertJsonPath('data.is_self', false);
});

test('The visited user profile is self', function() {
    $this->response
        ->getJson("/api/profile/{$this->user->username}")
        ->assertOk()
        ->assertJsonPath('data.is_self', true);
});

test('Should return the profile data with followers count and following count', function() {
    // Assume that the auth user is already following 40 users.
    $this->user->following()->sync(range(2, 41));
    $this->user->followers()->sync(range(51, 55));

    $this->response
        ->getJson("/api/profile/{$this->user->username}")
        ->assertOk()
        ->assertJsonPath('data.followers_count', 5)
        ->assertJsonPath('data.following_count', 40);
});

test('Should throw validation errors if input values are incorrect in update profile form', function() {
    $this->response
        ->putJson('/api/profile/update', [
            'bio' => $this->faker->paragraphs(5, true)
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'name' => ['The name field is required.'],
                'bio' => ['The number of characters exceeds the maximum length.'],
            ]
        ]);
});

test('Can\'t update the birth date that has been already set', function() {
    $this->response
        ->putJson('/api/profile/update', [
            'name' => $this->user->name,
            'birth_month' => 'January',
            'birth_day' => 12,
            'birth_year' => 1996,
        ])
        ->assertOk();
    
    $updatedUser = User::find($this->user->id);

    $this->assertTrue($this->user->birth_date === $updatedUser->birth_date);
});

test('Can update the profile successfully', function() {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/update', [
            'name' => 'John Doe',
            'location' => 'Philippines',
            'bio' => 'Hello World',
        ])
        ->assertOk();
    
    $updatedUser = User::where([
        ['id', '=', $user->id],
        ['name', '=', 'John Doe'],
        ['location', '=', 'Philippines'],
        ['bio', '=', 'Hello World'],
    ]);

    $this->assertTrue($updatedUser->exists());
});
