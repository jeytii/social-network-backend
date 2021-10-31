<?php

use App\Models\User;
use App\Notifications\SendVerificationCode;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\{DB, Event, Notification};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw an error if passwords don\'t match', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(route('auth.register'), [
        'password' => 'User@123',
        'password_confirmation' => 'asdasdasd'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.password_confirmation', ['Does not match with the password above.']);
    
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
        ->assertJsonPath('errors.username', ['Username must be between 6 and 30 characters long.']);

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
        ->assertJsonPath('errors.email', ['Email address already taken.'])
        ->assertJsonPath('errors.username', ['Username already taken.']);

    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should throw an error if the input has invalid format/regex', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(route('auth.register'), ['username' => 'u$ername@123'])
        ->assertStatus(422)
        ->assertJsonFragment([
            'username' => ['Invalid username.']
        ]);
        
    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should throw an error if phone number is invalid', function() {
    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(route('auth.register'), ['phone_number' => '12345678900'])
        ->assertStatus(422)
        ->assertJsonPath('errors.phone_number', ['Invalid phone number.']);
        
    Event::assertNothingDispatched();
    Notification::assertNothingSent();
});

test('Should successfully register an account', function() {
    $user = User::factory()->make();
    $birthDate = $user->birth_date->format('Y-m-d');
    $body = $user->only(['name', 'email', 'username', 'phone_number', 'gender']);
    $request = array_merge($body, [
        'birth_date' => $birthDate
    ]);

    Event::fake([Registered::class]);
    Notification::fake();
    
    $this->postJson(
        route('auth.register'),
        array_merge($request, [
            'password' => 'P@ssword123',
            'password_confirmation' => 'P@ssword123',
        ])
    )->assertCreated();

    $this->assertDatabaseHas('users', [
        'username' => $user->username,
        'email' => $user->email,
    ]);

    Event::assertDispatched(fn(Registered $event) => $event->user->username === $user->username);
    Notification::assertSentTo(User::firstWhere('username', $user->username), SendVerificationCode::class);
});
