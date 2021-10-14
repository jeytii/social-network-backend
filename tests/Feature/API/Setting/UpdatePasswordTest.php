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

test('Should throw an error for incorrect current password', function() {
    $this->response
        ->putJson(route('settings.change.password'), [
            'current_password' => 'password123'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.current_password', ['Incorrect password.']);
});

test('Should throw an error for confirmation does not match with new password', function() {
    $this->response
        ->putJson(route('settings.change.password'), [
            'new_password' => 'P@ssword12345',
            'new_password_confirmation' => 'wrongpassword',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.new_password_confirmation', ['Does not match with the password above.']);
});

test('Should throw an error for entering the current password as the new one', function() {
    $this->response
        ->putJson(route('settings.change.password'), [
            'new_password' => 'P@ssword123',
            'new_password_confirmation' => 'P@ssword123',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.new_password', ['Please enter a password other than your current one.']);
});

test('Should successfully update the password', function() {
    $this->response
        ->putJson(route('settings.change.password'), [
            'current_password' => 'P@ssword123',
            'new_password' => 'P@ssword12345',
            'new_password_confirmation' => 'P@ssword12345',
        ])
        ->assertOk();

    $this->assertTrue(Hash::check('P@ssword12345', $this->user->password));
});
