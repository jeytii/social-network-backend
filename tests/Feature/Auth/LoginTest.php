<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
});

test('Should throw an error if the username and password fields are not set', function() {
    $this->postJson(route('auth.login'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['username', 'password']);
});

test('Should throw an error if the username is not set', function() {
    $this->postJson(route('auth.login'), ['password' => 'password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('Should throw an error if the password is not set', function() {
    $this->postJson(route('auth.login'), ['username' => 'username'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('Should throw an error if the entered credentials don\'t exist', function() {
    $this->postJson(route('auth.login'), [
        'username' => 'username',
        'password' => 'password'
    ])->assertNotFound();
});

test('Should throw an error if a user is not yet verified', function() {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->postJson(route('auth.login'), [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])->assertUnauthorized();
});

test('Should return an auth token if successful', function() {
    $user = User::factory()->create();

    $this->postJson(route('auth.login'), [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'message', 'status']);
});
