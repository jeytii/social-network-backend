<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Hash, Cache};

test('Should throw an error if token is missing from the request body', function() {
    $data = [
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
    ];

    $this->putJson(route('auth.reset-password'), $data)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('Should throw an error if token is invalid', function() {
    $data = [
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
        'token' => '123456789',
    ];

    $this->putJson(route('auth.reset-password'), $data)->assertUnauthorized();
});

test('Should successfully reset the password', function() {
    $user = User::factory()->create();
    $token = bin2hex(random_bytes(16));
    $email = $user->email;
    $cacheKey = "password-reset.{$token}";
    $data = [
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
        'token' => $token,
    ];
    
    DB::table('password_resets')->insert(compact('email', 'token'));

    Cache::shouldReceive('has')
        ->once()
        ->with($cacheKey)
        ->andReturn(true);

    Cache::shouldReceive('get')
        ->once()
        ->with($cacheKey)
        ->andReturn($email);

    Cache::shouldReceive('forget')
        ->once()
        ->with($cacheKey);

    $this->putJson(route('auth.reset-password'), $data)->assertOk();
    
    $this->assertTrue(Hash::check('P@ssword12345', $user->refresh()->password));
});
