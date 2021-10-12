<?php

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\{DB, Event};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw an error if the username and password fields are not set', function() {
    Event::fake([Login::class]);

    $this->postJson(route('auth.login'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['username', 'password']);

    Event::assertNothingDispatched();
});

test('Should throw an error if the username is not set', function() {
    Event::fake([Login::class]);

    $this->postJson(route('auth.login'), ['password' => 'password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['username']);

    Event::assertNothingDispatched();
});

test('Should throw an error if the password is not set', function() {
    Event::fake([Login::class]);

    $this->postJson(route('auth.login'), ['username' => 'username'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);

    Event::assertNothingDispatched();
});

test('Should throw an error if the entered credentials don\'t exist', function() {
    Event::fake([Login::class]);

    $this->postJson(route('auth.login'), [
        'username' => 'username',
        'password' => 'password'
    ])->assertNotFound();

    Event::assertNothingDispatched();
});

test('Should throw an error if a user is not yet verified', function() {
    Event::fake([Login::class]);
    
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->postJson(route('auth.login'), [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])->assertUnauthorized();

    Event::assertNothingDispatched();
});

test('Should return an auth token if successful', function() {
    Event::fake([Login::class]);

    $user = User::factory()->create();

    $this->postJson(route('auth.login'), [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])
        ->assertOk()
        ->assertJsonStructure([
            'user' => array_shift(config('api.response.user.basic')),
            'token',
            'message',
            'status',
        ]);

    Event::assertDispatched(fn(Login $event) => $event->user->slug === $user->slug);
});
