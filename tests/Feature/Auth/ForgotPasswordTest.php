<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\{DB, Notification};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('password_resets')->truncate();
});

test('Should throw an error if email address is not set', function() {
    Notification::fake();

    $this->postJson('/api/forgot-password')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    
    Notification::assertNothingSent();
});

test('Should throw an error if email address has invalid format', function() {
    Notification::fake();

    $this->postJson('/api/forgot-password', ['email' => 'invalidemailaddress'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    
    Notification::assertNothingSent();
});

test('Should throw an error if the entered email address doesn\'t exist', function() {
    Notification::fake();

    $this->postJson('/api/forgot-password', ['email' => 'dummy@email.com'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    Notification::assertNothingSent();
});

test('Should send password reset request successfully', function() {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});
