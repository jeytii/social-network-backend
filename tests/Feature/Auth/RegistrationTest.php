<?php

use App\Models\User;
use App\Notifications\SendVerificationCode;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\{DB, Event, Notification};

/**
 * The required form fields.
 * 
 * @var array
 */
$requiredFields = [
    'name', 'email', 'username', 'phone_number',
    'gender', 'birth_month', 'birth_day', 'birth_year',
];

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw an error if all inputs are not set', function() use ($requiredFields) {
    Event::fake([Registered::class]);

    $this->postJson(route('auth.register'))
        ->assertStatus(422)
        ->assertJsonValidationErrors($requiredFields);

    Event::assertNothingDispatched();
});

test('Should throw an error if some inputs are not set', function() {
    Event::fake([Registered::class]);
    Notification::fake();

    $this->postJson(route('auth.register'), [
        'name' => 'John Doe',
        'password' => 'password'
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'email', 'username', 'phone_number', 'gender',
            'birth_month', 'birth_day', 'birth_year'
        ]);

    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should throw an error if passwords don\'t match', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(route('auth.register'), [
        'password' => 'User@123',
        'password_confirmation' => 'asdasdasd'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.password', ['Please confirm your password.']);
    
    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should throw an error if the character length is less than minimum', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(route('auth.register'), [
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
    Notification::assertNothingSent();
});

test('Should throw an error if the character length is out of range', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(route('auth.register'), [
        'username' => 'thebigbrownfoxjumpsoverthelazydog'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['The username must be between 6 to 30 characters long.']);

    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should throw an error is user already exists', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $user = User::factory()->create([
        'email' => 'email@example.com',
        'username' => 'dummy_user123',
    ]);
    
    $this->postJson(route('auth.register'), $user->only('email', 'username'))
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['You entered an unavailable email address.'])
        ->assertJsonPath('errors.username', ['You entered an unavailable username.']);

    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should throw an error if the input has invalid format/regex', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(route('auth.register'), ['username' => 'u$ername@123'])
        ->assertStatus(422)
        ->assertJsonFragment([
            'username' => ['Please enter a valid username.']
        ]);
        
    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should throw an error if phone number is invalid', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(route('auth.register'), ['phone_number' => '12345678900'])
        ->assertStatus(422)
        ->assertJsonPath('errors.phone_number', ['Please enter a valid phone number.']);
        
    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should successfully register an account', function() use ($requiredFields) {
    $user = User::factory()->make();
    $body = $user->only($requiredFields);

    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(
        route('auth.register'),
        array_merge($body, [
            'password' => 'P@ssword123',
            'password_confirmation' => 'P@ssword123',
            'prefers_sms_verification' => false,
        ])
    )->assertCreated();

    $this->assertDatabaseHas('users', [
        'username' => $user->username,
        'email' => $user->email,
    ]);

    Event::assertDispatched(fn(Registered $event) => $event->user->username === $user->username);
    Notification::assertSentTo(User::firstWhere('username', $user->username), SendVerificationCode::class);
});
