<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\{DB, Cache, Notification};

test('Should throw an error if the entered email address doesn\'t exist', function() {
    Notification::fake();

    $data = [
        'email' => 'dummy@email.com',
    ];

    $this->postJson(route('auth.forgot-password'), $data)
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['Email address does not exist.']);

    Notification::assertNothingSent();
});

test('Should throw an error if user has reached the rate limit', function() {
    $user = User::factory()->create();

    $resets = collect(range(1, 7))->map(fn($reset) => [
        'email' => $user->email,
        'completed_at' => now()->subHours($reset * 1),
    ])->toArray();

    DB::table('password_resets')->insert($resets);

    Notification::fake();

    $this->postJson(route('auth.forgot-password'), $user->only('email'))
        ->assertStatus(429);
    
    Notification::assertNothingSent();
});

test('Should send password reset request successfully', function() {
    $user = User::factory()->create();

    Notification::fake();
    Cache::spy();

    $this->postJson(route('auth.forgot-password'), $user->only('email'))
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
    Cache::shouldHaveReceived('put')->once();
});

test('Should throw an error if account is not yet verified', function() {
    $user = User::factory()->unverified()->create();

    $this->postJson(route('auth.forgot-password'), $user->only('email'))
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['Email address not yet verified.']);
});
