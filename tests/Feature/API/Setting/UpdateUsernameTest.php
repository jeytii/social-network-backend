<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Notification};
use App\Notifications\SendVerificationCode;

beforeAll(function() {
    User::factory()->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('username_updates')->truncate();
});

test('Should throw 422 errors in requesting to update the username', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.username'), [
            'prefers_sms' => 'true',
            'password' => 'password123'
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'username' => ['The username field is required.'],
                'prefers_sms' => ['Must be true or false only.'],
                'password' => ['Incorrect password.'],
            ]
        ]);

    Notification::assertNothingSent();
    $this->assertDatabaseCount('username_updates', 0);
});

test('Should throw an error for entering the current username', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.username'), [
            'username' => $this->user->username,
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.username', ['Please enter a valid username that is not owned by anyone.']);

    Notification::assertNothingSent();
    $this->assertDatabaseCount('username_updates', 0);
});

test('Should successfully make a request to update username via email', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.username'), [
            'username' => 'sampleuser_123',
            'prefers_sms' => false,
            'password' => 'P@ssword123'
        ])
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully request for username update.',
        ]);

    Notification::assertSentTo(
        $this->user,
        fn(SendVerificationCode $notification, $channels) => (
            $notification->prefersSMS === false &&
            $channels === ['mail']
        )
    );

    $this->assertDatabaseCount('username_updates', 1);
    $this->assertDatabaseHas('username_updates', [
        'user_id' => $this->user->id
    ]);
});

test('Should successfully make a request to update username via SMS', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.username'), [
            'username' => 'sampleuser_12345',
            'prefers_sms' => true,
            'password' => 'P@ssword123'
        ])
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully request for username update.',
        ]);

    Notification::assertSentTo(
        $this->user,
        fn(SendVerificationCode $notification, $channels) => (
            $notification->prefersSMS === true &&
            $channels === ['nexmo']
        )
    );

    $this->assertDatabaseCount('username_updates', 1);
    $this->assertDatabaseHas('username_updates', [
        'user_id' => $this->user->id
    ]);
});

test('Should throw an error if the verification code doesn\'t exist', function() {
    Notification::fake();

    $this->response
        ->putJson(route('settings.update.username'), [
            'code' => 123456
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'code' => ['Invalid verification code.']
            ]
        ]);

    Notification::assertNothingSent();
});

test('Should throw an error for attempting to update username with expired verification code', function() {
    $update = DB::table('username_updates')
                ->where('user_id', $this->user->id)
                ->whereNull('completed_at');
    
    $update->update(['expiration' => now()->subMinutes(40)]);

    Notification::fake();

    $this->response
        ->putJson(route('settings.update.username'), [
            'code' => $update->first()->code
        ])
        ->assertStatus(410);

    Notification::assertNothingSent();

    $this->assertDatabaseMissing('users', [
        'username' => $update->first()->data
    ]);

    DB::table('username_updates')->truncate();
});

test('Should successfully update the username', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.username'), [
            'username' => 'user012345',
            'prefers_sms' => false,
            'password' => 'P@ssword123'
        ])
        ->assertOk();

    Notification::assertSentTo($this->user, SendVerificationCode::class);

    $code = DB::table('username_updates')
                ->where('user_id', $this->user->id)
                ->whereNull('completed_at')
                ->first()->code;

    $this->response
        ->putJson(route('settings.update.username'), compact('code'))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully updated the username.',
        ]);

    $this->assertDatabaseHas('users', [
        'username' => 'user012345',
    ]);
});
