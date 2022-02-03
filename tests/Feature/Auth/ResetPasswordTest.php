<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Hash, Cache};

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
    DB::table('personal_access_tokens')->truncate();
    DB::table('jobs')->truncate();
});

test('Should throw an error if token is missing from the request body', function() {
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
    ])->assertUnauthorized();
});

test('Should successfully reset the password', function() {
    $token = bin2hex(random_bytes(16));
    $email = $this->user->email;
    $cacheKey = "password-reset.{$token}";
    
    DB::table('password_resets')->insert(compact('email', 'token'));

    Cache::shouldReceive('has')->once()->with($cacheKey)->andReturn(true);
    Cache::shouldReceive('get')->once()->with($cacheKey)->andReturn($email);
    Cache::shouldReceive('forget')->once()->with($cacheKey);

    $this->putJson(route('auth.reset-password'), [
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
        'token' => $token,
    ])->assertOk();

    $user = DB::table('users')->where('email', $email)->first();

    $this->assertTrue(Hash::check('P@ssword12345', $user->password));
});
