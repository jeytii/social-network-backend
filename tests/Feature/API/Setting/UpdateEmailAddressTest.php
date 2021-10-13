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
        ->putJson(route('settings.change.email'), [
            'email' => $this->user->email,
            'password' => 'wrongpassword',
        ])
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
    $this->response
        ->putJson(route('settings.change.email'), [
            'email' => $this->user->email,
            'password' => 'P@ssword123',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['Email address already taken.']);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'email',
    ]);
});

test('Should successfully update the email address', function() {
    $this->response
        ->putJson(route('settings.change.email'), [
            'email' => 'johndoe@email.com',
            'password' => 'P@ssword123',
        ])
        ->assertOk();

    $this->assertDatabaseHas('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'email',
    ]);
});
