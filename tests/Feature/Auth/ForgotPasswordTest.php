<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\{DB, Hash, Notification};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('password_resets')->truncate();
});

test('Should throw an error if the entered email address doesn\'t exist', function() {
    Notification::fake();

    $this->postJson(route('auth.forgot-password'), [
        'email' => 'dummy@email.com',
        'method' => 'email',
    ])
    ->assertStatus(422)
    ->assertJsonPath('errors.email', ['Email address does not exist.']);

    Notification::assertNothingSent();
});

test('Should throw an error if user has reached the rate limit', function() {
    $user = User::factory()->create();

    $resets = collect(range(1, 7))->map(fn($reset) => [
        'email' => $user->email,
        'token' => Hash::make($user->email),
        'expiration' => now()->subHours($reset * 1),
        'completed_at' => now()->subHours($reset * 1),
    ])->toArray();

    DB::table('password_resets')->insert($resets);

    Notification::fake();

    $this->postJson(route('auth.forgot-password'), [
        'email' => $user->email,
        'method' => 'email',
    ])->assertStatus(429);
    
    Notification::assertNothingSent();
});

test('Should send password reset request successfully', function() {
    $user = User::factory()->create();

    Notification::fake();

    $this->postJson(route('auth.forgot-password'), [
        'email' => $user->email,
        'method' => 'email',
    ])->assertOk();

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        fn($n, $channels) => $channels === ['mail']
    );

    $this->postJson(route('auth.forgot-password'), [
        'email' => $user->email,
        'method' => 'sms',
    ])->assertOk();

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        fn($n, $channels) => $channels === ['nexmo']
    );

    $this->assertDatabaseHas('password_resets', [
        'email' => $user->email,
        'completed_at' => null,
    ]);
});

test('Should throw an error if account is not yet verified', function() {
    $user = User::factory()->create([
        'email_verified_at' => null
    ]);

    $this->postJson(route('auth.forgot-password'), [
        'email' => $user->email,
        'method' => 'email',
    ])
    ->assertStatus(422)
    ->assertJsonPath('errors.email', ['Email address not yet verified.']);
});
