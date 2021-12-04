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
        ->putJson(route('settings.change.phone-number'), [
            'phone_number' => '09abc',
            'password' => 'wrongpassword',
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'phone_number' => ['Invalid phone number.'],
                'password' => ['Incorrect password.'],
            ]
        ]);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'phone_number',
    ]);
});

test('Should throw an error for entering the current phone number', function() {
    $this->response
        ->putJson(route('settings.change.phone-number'), [
            'phone_number' => $this->user->phone_number,
            'password' => 'P@ssword123',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.phone_number', [
            'Please enter a phone number other than your current one.',
            'Phone number is already owned by someone else.',
        ]);

    $this->assertDatabaseMissing('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'phone_number',
    ]);
});

test('Should successfully update the phone number', function() {
    $this->response
        ->putJson(route('settings.change.phone-number'), [
            'phone_number' => '09123456789',
            'password' => 'P@ssword123'
        ])
        ->assertOk();

    $this->assertDatabaseHas('settings_updates', [
        'user_id' => $this->user->id,
        'type' => 'phone_number',
    ]);
});
