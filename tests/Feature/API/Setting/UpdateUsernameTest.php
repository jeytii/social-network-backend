<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory()->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('settings_updates')->truncate();
});

test('Should throw errors for invalid inputs', function() {
    $this->response
        ->putJson(route('settings.change.username'), [
            'username' => '$ampleusername',
            'password' => 'wrongpassword',
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'username' => ['Invalid username.'],
                'password' => ['Incorrect password.'],
            ]
        ]);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'username',
    ]);
});

test('Should throw an error if the length is out of range', function() {
    $this->response
        ->putJson(route('settings.change.username'), [
            'username' => 'user',
            'password' => 'P@ssword123',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['Username must be between 6 and 30 characters long.']);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'username',
    ]);
});

test('Should throw an error for entering the current username', function() {
    $this->response
        ->putJson(route('settings.change.username'), [
            'username' => $this->user->username,
            'password' => 'P@ssword123',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['Username already taken.']);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'username',
    ]);
});

test('Should successfully update the username', function() {
    $this->response
        ->putJson(route('settings.change.username'), [
            'username' => 'user012345',
            'password' => 'P@ssword123',
        ])
        ->assertOk();

    $this->assertDatabaseHas('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'username',
    ]);
});
