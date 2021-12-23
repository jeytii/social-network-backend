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
        'token' => bin2hex(random_bytes(16)),
        'code' => random_int(100000, 999999),
        'expiration' => now()->addMinutes(config('validation.expiration.verification')),
    ]);
});

beforeEach(function() {
    $this->verification = DB::table('verifications')->first();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('verifications')->truncate();
});

test('Should throw an error if the user is already verified.', function() {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson(route('auth.verify.resend'), $user->only('username'))
        ->assertStatus(409);

    Notification::assertNothingSent();
});

test('Can request for another verification code', function() {
    $user = User::first();
    
    Notification::fake();

    $this->postJson(route('auth.verify.resend'), $user->only('username'))
        ->assertOk();

    Notification::assertSentTo($user, SendVerificationCode::class);
});

test('Should throw an error for verifying account with invalid verification username or invalid code', function() {
    $this->putJson(route('auth.verify'), [
        'code' => $this->verification->code
    ])->assertOk();
    
    $this->putJson(route('auth.verify'), ['code' => 123456])
        ->assertStatus(422);
});

test('Should successfully verify account', function() {
    User::where('id', $this->verification->user_id)->update([
        'email_verified_at' => null
    ]);

    $this->putJson(route('auth.verify'), [
        'code' => $this->verification->code
    ])->assertOk();
});

test('Should throw an error for verifying account with expired verification code', function() {
    DB::table('verifications')
        ->where('id', $this->verification->id)
        ->update(['expiration' => now()->subDay()]);

    $this->putJson(route('auth.verify'), [
        'code' => $this->verification->code
    ])
        ->assertUnauthorized();
});
