<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\{DB, Event};

/**
 * The required form fields.
 * 
 * @var array
 */
$requiredFields = [
    'name', 'email', 'username', 'gender', 'password',
    'phone_number', 'birth_month', 'birth_day', 'birth_year',
];

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw an error if all inputs are not set', function() use ($requiredFields) {
    Event::fake([Registered::class]);

    $this->postJson('/api/register')
        ->assertStatus(422)
        ->assertJsonValidationErrors($requiredFields);

    Event::assertNothingDispatched();
});

test('Should throw an error if some inputs are not set', function() {
    Event::fake([Registered::class]);

    $this->postJson('/api/register', [
        'name' => 'John Doe',
        'password' => 'password'
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'email', 'username', 'phone_number', 'gender',
            'birth_month', 'birth_day', 'birth_year'
        ]);

    Event::assertNothingDispatched();
});

test('Should throw password mismatch error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/api/register', [
        'password' => 'User@123',
        'password_confirmation' => 'asdasdasd'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.password', ['Please confirm your password.']);
    
    Event::assertNothingDispatched();
});

test('Should throw minimum length error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/api/register', [
        'name' => 'z',
        'username' => 'abc',
        'password' => 'abc',
        'password_confirmation' => 'abc',
    ])
        ->assertStatus(422)
        ->assertJsonStructure([
            'errors' => ['name', 'username', 'password']
        ]);

    Event::assertNothingDispatched();
});

test('Should throw character length range error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/api/register', [
        'username' => 'thebigbrownfoxjumpsoverthelazydog'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['The username must be between 6 to 30 characters long.']);

    Event::assertNothingDispatched();
});

test('Should throw "already exists" error', function() {
    Event::fake([Registered::class]);
    
    $user = User::factory()->create([
        'email' => 'email@example.com',
        'username' => 'dummy.user123',
    ]);
    
    $this->postJson('/api/register', $user->only('email', 'username'))
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['You entered an unavailable email address.'])
        ->assertJsonPath('errors.username', ['You entered an unavailable username.']);

    Event::assertNothingDispatched();
});

test('Should throw regex pattern error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/api/register', ['username' => 'u$ername@123'])
        ->assertStatus(422)
        ->assertJsonFragment([
            'username' => ['Please enter a valid username.']
        ]);
        
    Event::assertNothingDispatched();
});

test('Should throw invalid phone number error', function() {
    Event::fake([Registered::class]);
    
    $this->postJson('/api/register', ['phone_number' => '12345678900'])
        ->assertStatus(422)
        ->assertJsonPath('errors.phone_number', ['Please enter a valid phone number.']);
        
    Event::assertNothingDispatched();
});

test('Successful registration', function() use ($requiredFields) {
    $user = User::factory()->make();
    $body = $user->only($requiredFields);

    Event::fake([Registered::class]);
    
    $this->postJson(
        '/api/register',
        array_merge($body, ['password_confirmation' => $user->password])
    )->assertCreated();

    test()->assertDatabaseHas('users', [
        'username' => $user->username,
        'email' => $user->email,
    ]);

    Event::assertDispatched(fn(Registered $event) => $event->user->username === $user->username);
});
