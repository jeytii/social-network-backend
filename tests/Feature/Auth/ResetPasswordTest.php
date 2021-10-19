<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\{DB, Event, Hash, Notification};

beforeAll(function() {
    $user = User::factory()->create();

    $resets = collect(range(1, 7))->map(fn($reset) => [
        'email' => $user->email,
        'token' => Hash::make($user->email),
        // 'expiration' => now()->subHours($reset * 1),
        // 'completed_at' => now()->subHours($reset * 1),
    ])->toArray();

    DB::table('password_resets')->insert($resets);
});

beforeEach(function() {
    $this->user = User::first();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('password_resets')->truncate();
});

test('Should throw an error if token is missing', function() {
    Event::fake([PasswordReset::class]);

    $this->putJson(route('auth.reset-password'), [
        'email' => $this->user->email,
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
    ])->assertStatus(422);

    Event::assertNothingDispatched();
});

test('Should throw an error if email address is missing', function() {
    Event::fake([PasswordReset::class]);

    $this->putJson(route('auth.reset-password'), [
        'token' => Hash::make($this->user->email),
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
    ])->assertStatus(422);

    Event::assertNothingDispatched();
});

test('Should throw an error if token is invalid', function() {
    Event::fake([PasswordReset::class]);

    $this->putJson(route('auth.reset-password'), [
        'email' => $this->user->email,
        'token' => Hash::make($this->user->email),
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
    ])->assertUnauthorized();

    Event::assertNothingDispatched();
});

test('Should successfully reset the password', function() {
    Notification::fake();
    
    $this->postJson(route('auth.forgot-password'), $this->user->only('email'))
        ->assertOk();
    
    Event::fake([PasswordReset::class]);

    $passwordReset = DB::table('password_resets')->where('email', $this->user->email)->first();
    
    $this->putJson(route('auth.reset-password'), [
        'email' => $passwordReset->email,
        'password' => 'P@ssword12345',
        'password_confirmation' => 'P@ssword12345',
        'token' => $passwordReset->token,
    ])->assertOk();

    $user = DB::table('users')->where('email', $passwordReset->email)->first();

    $this->assertTrue(Hash::check('P@ssword12345', $user->password));
    Event::assertDispatched(fn(PasswordReset $event) => $event->user->id === $user->id);
});
