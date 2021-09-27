<?php

use App\Models\User;
use App\Notifications\SendVerificationCode;
use Illuminate\Support\Facades\{DB, Notification};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw errors for non-existing username and invalid prefers_sms type', function() {
    Notification::fake();

    $this->postJson(route('auth.verify.resend'), [
        'username' => 'invaliduser',
        'prefers_sms_verification' => 'true',
    ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'username' => ['User does not exist.'],
            'prefers_sms_verification' => ['Must be only true or false.'],
        ]);

    Notification::assertNothingSent();
});

test('Should throw an error if the user is already verified.', function() {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson(route('auth.verify.resend'), [
        'username' => $user->username,
        'prefers_sms_verification' => true,
    ])->assertStatus(409);

    Notification::assertNothingSent();
});

test('Can enter email address and send SMS notification', function() {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null
    ]);

    $this->postJson(route('auth.verify.resend'), [
        'username' => $user->email,
        'prefers_sms_verification' => true,
    ])
        ->assertStatus(200)
        ->assertExactJson([
            'status' => 200,
            'message' => 'A verification code has been sent your phone number.'
        ]);

    Notification::assertSentTo(
        $user,
        SendVerificationCode::class,
        fn($notification) => $notification->prefersSMS === true
    );
});

test('Can enter username and send email notification', function() {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null
    ]);

    $this->postJson(route('auth.verify.resend'), [
        'username' => $user->username,
        'prefers_sms_verification' => false,
    ])
        ->assertStatus(200)
        ->assertExactJson([
            'status' => 200,
            'message' => 'A verification code has been sent your email address.'
        ]);

    Notification::assertSentTo(
        $user,
        SendVerificationCode::class,
        fn($notification) => !$notification->prefersSMS
    );
});
