<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Hash};

beforeAll(function() {
    User::factory()->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw an error in if all inputs are blank', function() {
    $this->response
        ->putJson(route('settings.update.password'))
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'current_password' => ['Please enter your current password.'],
                'new_password' => ['Please enter your new password.'],
            ]
        ]);
});

test('Should throw an error for incorrect current password', function() {
    $this->response
        ->putJson(route('settings.update.password'), [
            'current_password' => 'password123'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.current_password', ['Incorrect password.']);
});

test('Should throw an error for unconfirmed new password', function() {
    $this->response
        ->putJson(route('settings.update.password'), [
            'new_password' => 'P@ssword12345',
            'new_password_confirmation' => 'wrongpassword',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.new_password', ['New password must match with the confirmation.']);
});

test('Should throw an error for entering the current password as the new one', function() {
    $this->response
        ->putJson(route('settings.update.password'), [
            'new_password' => 'P@ssword123',
            'new_password_confirmation' => 'P@ssword123',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.new_password', ['Please enter a password other than your current one.']);
});

test('Should successfully update the password', function() {
    $this->response
        ->putJson(route('settings.update.password'), [
            'current_password' => 'P@ssword123',
            'new_password' => 'P@ssword12345',
            'new_password_confirmation' => 'P@ssword12345',
        ])
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully updated the password.',
        ]);

    $this->assertTrue(Hash::check('P@ssword12345', $this->user->password));
});
