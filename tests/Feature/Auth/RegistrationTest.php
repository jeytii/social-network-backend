<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\{DB, Event};

/**
 * The required form fields.
 * 
 * @var array
 */
$requiredFields = ['name', 'email', 'username', 'gender', 'password'];

test('Should throw an error if all inputs are not set', function() use ($requiredFields) {
    Event::fake([Registered::class]);

    $this->postJson('/register')
        ->assertStatus(422)
        ->assertJsonValidationErrors($requiredFields);

    Event::assertNothingDispatched();
});

test('Should throw an error if some inputs are not set', function() {
    Event::fake([Registered::class]);

    $this->postJson('/register', [
        'name' => 'John Doe',
        'password' => 'password'
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'username', 'gender' ]);

    Event::assertNothingDispatched();
});

test('Should throw password mismatch error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/register', [
        'password' => 'User@123',
        'password_confirmation' => 'asdasdasd'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.password', ['The password confirmation does not match.']);
    
    Event::assertNothingDispatched();
});

test('Should throw minimum length error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/register', [
        'name' => 'z',
        'username' => 'abc',
        'password' => 'abc',
        'password_confirmation' => 'abc',
    ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'name' => ['The name must be at least 2 characters long.'],
            'username' => ['The username must be 6 to 20 characters long.'],
            'password' => ['The password must be at least 8 characters long and have one uppercase letter, number, and special character.'],
        ]);

    Event::assertNothingDispatched();
});

test('Should throw character length range error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/register', [
        'username' => 'thebigbrownfoxjumpsoverthelazydog'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['The username must be 6 to 20 characters long.']);

    Event::assertNothingDispatched();
});

test('Should throw "already exists" error', function() {
    Event::fake([Registered::class]);
    
    $user = User::factory()->create([
        'email' => 'email@example.com',
        'username' => 'dummy.user123',
    ]);
    
    $this->postJson('/register', $user->only('email', 'username'))
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['Someone has already taken that email address.'])
        ->assertJsonPath('errors.username', ['Someone has already taken that username.']);

    Event::assertNothingDispatched();

    DB::table('users')->truncate();
});

test('Should throw regex pattern error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/register', ['username' => 'u$ername@123'])
        ->assertStatus(422)
        ->assertJsonFragment([
            'username' => ['Only letters, numbers, dots, and underscores are allowed.']
        ]);
        
    Event::assertNothingDispatched();
});

test('Successful registration', function() use ($requiredFields) {
    $user = User::factory()->make();
    $body = collect($user->only($requiredFields));

    Event::fake([Registered::class]);
    
    $this->postJson(
        '/register',
        $body->merge(['password_confirmation' => $user->password])->toArray()
    )->assertCreated();

    test()->assertDatabaseHas('users', [
        'username' => $user->username,
        'email' => $user->email,
    ]);

    Event::assertDispatched(fn(Registered $event) => $event->user->username === $user->username);
    Event::assertListening(Registered::class, SendEmailVerificationNotification::class);

    DB::table('users')->truncate();
});
