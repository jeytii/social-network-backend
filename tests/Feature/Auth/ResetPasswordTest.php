<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\{DB, Event, Hash, Notification};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('password_resets')->truncate();
});

test('Should throw an error if all inputs are not set', function() {
    Event::fake([PasswordReset::class]);

    $this->putJson(route('auth.reset-password'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'token']);
    
    Event::assertNothingDispatched();
});

// test('Resetting the password successfully', function() {
//     Notification::fake();

//     $user = User::factory()->create();
    
//     $this->postJson('/forgot-password', ['email' => $user->email])
//         ->assertOk();
    
//     Event::fake([ PasswordReset::class ]);

//     $passwordReset = DB::table('password_resets')->where('email', $user->email)->first();
    
//     $this->putJson('/reset-password', [
//         'email' => $passwordReset->email,
//         'password' => 'P@ssword12345',
//         'password_confirmation' => 'P@ssword12345',
//         'token' => $passwordReset->token,
//     ])->assertOk();

//     $user = DB::table('users')->where('email', $passwordReset->email)->first();

//     $this->assertTrue(Hash::check('P@ssword12345', $user->password));
//     Event::assertDispatched(fn(PasswordReset $event) => $event->user->id === $user->id);
// });
