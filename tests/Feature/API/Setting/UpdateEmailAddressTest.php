<?php

use App\Models\User;

beforeEach(function() {
    $this->user = User::factory()->create();

    authenticate();
});

test('Should throw errors for invalid inputs', function() {
    $data = [
        'email' => $this->user->email,
        'password' => 'wrongpassword',
    ];

    $this->putJson(route('settings.change.email'), $data)
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'email' => ['Email address already taken.'],
                'password' => ['Incorrect password.'],
            ]
        ]);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'email',
    ]);
});

test('Should throw an error for entering the current email address', function() {
    $data = [
        'email' => $this->user->email,
        'password' => 'P@ssword123',
    ];

    $this->putJson(route('settings.change.email'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['Email address already taken.']);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'email',
    ]);
});

test('Should successfully update the email address', function() {
    $data = [
        'email' => 'johndoe@email.com',
        'password' => 'P@ssword123',
    ];
    
    $this->putJson(route('settings.change.email'), $data)
        ->assertOk();

    $this->assertDatabaseHas('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'email',
    ]);
});
