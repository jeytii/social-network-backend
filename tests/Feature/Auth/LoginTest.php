<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

afterEach(fn() => DB::table('users')->truncate());

test('Should throw an error if the username and password fields are not set', function() {
    Event::fake([ Login::class ]);

    $this->postJson('/api/login')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['username', 'password']);

    Event::assertNothingDispatched();
});

test('Should throw an error if the username is not set', function() {
    Event::fake([ Login::class ]);

    $this->postJson('/api/login', [ 'password' => 'password' ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['username']);

    Event::assertNothingDispatched();
});

test('Should throw an error if the password is not set', function() {
    Event::fake([ Login::class ]);

    $this->postJson('/api/login', [ 'username' => 'username' ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);

    Event::assertNothingDispatched();
});

test('Should throw an error if the entered credentials don\'t exist', function() {
    Event::fake([ Login::class ]);

    $this->postJson('/api/login', [
        'username' => 'username',
        'password' => 'password'
    ])
        ->assertStatus(422)
        ->assertJsonFragment([ 'message' => 'Cannot find username and password combination.' ]);

    Event::assertNothingDispatched();
});

test('Should throw an error if a user is not yet verified', function() {
    Event::fake([ Login::class ]);
    
    $user = User::factory()->create([ 'email_verified_at' => null ]);

    $this->postJson('/api/login', [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])
        ->assertUnauthorized()
        ->assertJsonFragment([ 'message' => 'Your account is not yet verified.' ]);

    Event::assertNothingDispatched();
});

test('Should return an auth token if successful', function() {
    Event::fake([ Login::class ]);

    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])
        ->assertOk()
        ->assertJsonStructure(['token']);

    Event::assertDispatched(fn(Login $event) => $event->user->id === $user->id);
});
