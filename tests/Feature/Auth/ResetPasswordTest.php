<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Hash, Notification, Cache};

beforeAll(function() {
    User::factory()->create();
});

beforeEach(function() {
    $this->user = User::first();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('password_resets')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should throw an error if token is missing', function() {
    $this->putJson(route('auth.reset-password'), [
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
    ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['token']);
});

test('Should throw an error if token is invalid', function() {
    $this->putJson(route('auth.reset-password'), [
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
        'token' => '123456789',
    ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['token']);
});

test('Should successfully reset the password', function() {
    Notification::fake();
    
    $this->postJson(route('auth.forgot-password'), $this->user->only('email'))
        ->assertOk();
    
    $passwordReset = DB::table('password_resets')->where('email', $this->user->email)->first();

    $this->putJson(route('auth.reset-password'), [
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
        'token' => $passwordReset->token,
    ])->assertOk();

    $user = DB::table('users')->where('email', $this->user->email)->first();

    $this->assertTrue(Hash::check('P@ssword12345', $user->password));
});
