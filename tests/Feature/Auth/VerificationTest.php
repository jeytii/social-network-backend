<?php

use App\Models\User;
use App\Notifications\SendVerificationCode;
use Illuminate\Support\Facades\{DB, Notification};

beforeAll(function() {
    $user = User::factory()->create([
        'email_verified_at' => null
    ]);

    DB::table('verifications')->insert([
        'user_id' => $user->id,
        'code' => random_int(100000, 999999),
        'prefers_sms' => false,
        'expiration' => now()->addMinutes(config('validation.expiration.verification')),
    ]);
});

beforeEach(function() {
    $this->user = User::first();
    $this->verification = DB::table('verifications')->first();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
});

test('Should throw errors for non-existing username and invalid prefers_sms type', function() {
    Notification::fake();

    $this->postJson(route('auth.verify.resend'), [
        'username' => 'invaliduser',
        'prefers_sms' => 'true',
    ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'username' => ['User does not exist.'],
            'prefers_sms' => ['Must be true or false only.'],
        ]);

    Notification::assertNothingSent();
});

test('Should throw an error if the user is already verified.', function() {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson(route('auth.verify.resend'), [
        'username' => $user->username,
        'prefers_sms' => true,
    ])->assertStatus(409);

    Notification::assertNothingSent();
});

test('Can enter email address and resend SMS notification', function() {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null
    ]);

    $this->postJson(route('auth.verify.resend'), [
        'username' => $user->email,
        'prefers_sms' => true,
    ])
        ->assertOk()
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

test('Can enter username and resend email notification', function() {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null
    ]);

    $this->postJson(route('auth.verify.resend'), [
        'username' => $user->username,
        'prefers_sms' => false,
    ])
        ->assertOk()
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

test('Should throw an error for verifying account with invalid verification username or invalid code', function() {
    $this->putJson(route('auth.verify'), [
        'username' => 'invalidusername',
        'code' => $this->verification->code
    ])->assertNotFound();

    $this->putJson(route('auth.verify'), [
        'username' => $this->user->username,
        'code' => 123456
    ])->assertStatus(422);
});

test('Should successfully verify account', function() {
    $this->putJson(route('auth.verify'), [
        'username' => $this->user->email,
        'code' => $this->verification->code
    ])
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'You have successfully verified your account.',
        ]);
});

test('Should throw an error for verifying account with expired verification code', function() {
    DB::table('verifications')
        ->where('id', $this->verification->id)
        ->update(['expiration' => now()->subDay()]);

    $this->putJson(route('auth.verify'), [
        'username' => $this->user->username,
        'code' => $this->verification->code
    ])->assertUnauthorized();
});
