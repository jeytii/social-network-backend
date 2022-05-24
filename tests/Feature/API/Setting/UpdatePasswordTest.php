<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function() {
    $this->user = User::factory()->create();

    authenticate();
});

test('Should throw an error for incorrect current password', function() {
    $data = [
        'current_password' => 'password123'
    ];

    $this->putJson(route('settings.change.password'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.current_password', ['Incorrect password.']);
});

test('Should throw an error for confirmation does not match with new password', function() {
    $data = [
        'new_password' => 'P@ssword12345',
        'new_password_confirmation' => 'wrongpassword',
    ];

    $this->putJson(route('settings.change.password'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.new_password_confirmation', ['Does not match with the password above.']);
});

test('Should throw an error for entering the current password as the new one', function() {
    $data = [
        'new_password' => 'P@ssword123',
        'new_password_confirmation' => 'P@ssword123',
    ];

    $this->putJson(route('settings.change.password'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.new_password', ['Please enter a password other than your current one.']);
});

test('Should successfully update the password', function() {
    $oldPassword = 'P@ssword123';
    $newPassword = 'P@ssword12345';
    $data = [
        'current_password' => $oldPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPassword,
    ];

    $this->assertTrue(Hash::check($oldPassword, $this->user->password));

    $this->putJson(route('settings.change.password'), $data)
        ->assertOk();

    $this->assertTrue(Hash::check($newPassword, $this->user->refresh()->password));
});
