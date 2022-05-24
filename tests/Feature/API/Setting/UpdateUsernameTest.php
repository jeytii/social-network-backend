<?php

use App\Models\User;

beforeEach(function() {
    $this->user = User::factory()->create();

    authenticate();
});

test('Should throw errors for invalid inputs', function() {
    $data = [
        'username' => '$ampleusername',
        'password' => 'wrongpassword',
    ];

    $this->putJson(route('settings.change.username'), $data)
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
    $data = [
        'username' => 'user',
        'password' => 'P@ssword123',
    ];

    $this->putJson(route('settings.change.username'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['Username must be between 6 and 30 characters long.']);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'username',
    ]);
});

test('Should throw an error for entering the current username', function() {
    $data = [
        'username' => $this->user->username,
        'password' => 'P@ssword123',
    ];

    $this->putJson(route('settings.change.username'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['Username already taken.']);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'username',
    ]);
});

test('Should successfully update the username', function() {
    $data = [
        'username' => 'user012345',
        'password' => 'P@ssword123',
    ];
    
    $this->putJson(route('settings.change.username'), $data)
        ->assertOk();

    $this->assertDatabaseHas('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'username',
    ]);
});
