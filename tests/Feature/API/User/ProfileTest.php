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
    $this->response
        ->putJson('/api/profile/update', [
            'name' => $this->user->name,
            'birth_month' => 'January',
            'birth_day' => 12,
            'birth_year' => 1996,
        ])
        ->assertOk();
    
    $updatedUser = User::find($this->user->id);

    $this->assertTrue($this->user->full_birth_date === $updatedUser->full_birth_date);
});

test('Can update the profile successfully', function() {
    $user = User::factory()->create([
        'birth_month' => null,
        'birth_day' => null,
        'birth_year' => null,
    ]);

    $this->actingAs($user)
        ->putJson('/api/profile/update', [
            'name' => 'John Doe',
            'birth_month' => 'December',
            'birth_day' => 10,
            'birth_year' => 1990,
            'location' => 'Philippines',
            'bio' => 'Hello World',
        ])
        ->assertOk();
    
    $updatedUser = User::find($user->id);

    $this->assertTrue($updatedUser->name === 'John Doe');
    $this->assertTrue($updatedUser->full_birth_date === 'December 10, 1990');
    $this->assertTrue($updatedUser->location === 'Philippines');
    $this->assertTrue($updatedUser->bio === 'Hello World');
});
