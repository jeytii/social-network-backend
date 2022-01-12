<?php

use App\Models\User;
use App\Notifications\SendVerificationCode;
use Illuminate\Support\Facades\{DB, Notification, Cache};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw an error if passwords don\'t match', function() {
    Notification::fake();
    
    $this->postJson(route('auth.register'), [
        'password' => 'User@123',
        'password_confirmation' => 'asdasdasd'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.password_confirmation', ['Does not match with the password above.']);
    
    Notification::assertNothingSent();
});

test('Should throw an error if the character length is less than minimum', function() {
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

    Notification::assertNothingSent();
});

test('Should throw an error if the character length is out of range', function() {
    Notification::fake();
    
    $this->postJson(route('auth.register'), [
        'username' => 'thebigbrownfoxjumpsoverthelazydog'
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['Username must be between 6 and 30 characters long.']);

    Notification::assertNothingSent();
});

test('Should throw an error is user already exists', function() {
    Notification::fake();
    
    $user = User::factory()->create([
        'email' => 'email@example.com',
        'username' => 'dummy_user123',
    ]);
    
    $this->postJson(route('auth.register'), $user->only('email', 'username'))
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['Email address already taken.'])
        ->assertJsonPath('errors.username', ['Username already taken.']);

    Notification::assertNothingSent();
});

test('Should throw an error if the input has invalid format/regex', function() {
    Notification::fake();
    
    $this->postJson(route('auth.register'), ['username' => 'u$ername@123'])
        ->assertStatus(422)
        ->assertJsonFragment([
            'username' => ['Invalid username.']
        ]);
        
    Notification::assertNothingSent();
});

test('Should throw an error for invalid birth date format', function() {
    $this->postJson(route('auth.register'), ['birth_date' => '0020-01-01'])
        ->assertStatus(422)
        ->assertJsonPath('errors.birth_date', ['You must be 18 to 100 years old.']);

    $this->postJson(route('auth.register'), ['birth_date' => '2021-13-01'])
        ->assertStatus(422)
        ->assertJsonPath('errors.birth_date', ['Invalid birth date.', 'You must be 18 to 100 years old.']);

    $this->postJson(route('auth.register'), ['birth_date' => '0020-01-40'])
        ->assertStatus(422)
        ->assertJsonPath('errors.birth_date', ['Invalid birth date.', 'You must be 18 to 100 years old.']);

    $this->postJson(route('auth.register'), ['birth_date' => '2019-02-30'])
        ->assertStatus(422)
        ->assertJsonPath('errors.birth_date', ['Invalid birth date.', 'You must be 18 to 100 years old.']);
});

test('Should throw an error for entering a birth date earlier than 100 years ago', function() {
    $this->postJson(route('auth.register'), [
        'birth_date' => now()->subYears(101)->format('Y-m-d')
    ])
    ->assertStatus(422)
    ->assertJsonPath('errors.birth_date', ['You must be 18 to 100 years old.']);
});

test('Should throw an error for entering a birth date later than 18 years ago', function() {
    $this->postJson(route('auth.register'), [
        'birth_date' => now()->subYears(16)->format('Y-m-d')
    ])
    ->assertStatus(422)
    ->assertJsonPath('errors.birth_date', ['You must be 18 to 100 years old.']);
});

test('Should successfully register an account', function() {
    $user = User::factory()->make([
        'birth_date' => '1998-05-05'
    ]);
    
    $body = $user->only(['name', 'email', 'username', 'gender', 'birth_date']);

    Notification::fake();
    Cache::spy();
    
    $this->postJson(
        route('auth.register'),
        array_merge($body, [
            'password' => 'P@ssword123',
            'password_confirmation' => 'P@ssword123',
        ])
    )->assertCreated();

    $this->assertDatabaseHas('users', [
        'username' => $user->username,
        'email' => $user->email,
    ]);

    Cache::shouldHaveReceived('put')->once();
    Notification::assertSentTo(User::firstWhere('username', $user->username), SendVerificationCode::class);
});
