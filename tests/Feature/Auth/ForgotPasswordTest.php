<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\{DB, Notification};
use Illuminate\Auth\Notifications\ResetPassword;

afterEach(function() {
    DB::table('users')->truncate();
    DB::table('password_resets')->truncate();
});

test('Should throw an error if email address is not set', function() {
    Notification::fake();

    $this->postJson('/forgot-password')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    
    Notification::assertNothingSent();
});

test('Should throw an error if email address has invalid format', function() {
    Notification::fake();

    $this->postJson('/forgot-password', ['email' => 'invalidemailaddress'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    
    Notification::assertNothingSent();
});

test('Should throw an error if the entered email address doesn\'t exist', function() {
    Notification::fake();

    $this->postJson('/forgot-password', ['email' => 'dummy@email.com'])
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ["We can't find a user with that email address."]);

    Notification::assertNothingSent();
});

test('Should send password reset request successfully', function() {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson('/forgot-password', ['email' => $user->email])->assertOk();

    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});
