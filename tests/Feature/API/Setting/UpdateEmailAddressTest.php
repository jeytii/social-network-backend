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
});

test('Should throw 422 errors in requesting to update the email address', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.email'), [
            'password' => 'password123'
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'email' => ['The email address field is required.'],
                'password' => ['Incorrect password.'],
            ]
        ]);

    Notification::assertNothingSent();
    $this->assertDatabaseCount('email_address_updates', 0);
});

test('Should throw an error for entering the current email address', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.email'), [
            'email' => $this->user->email,
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.email', ['Please enter an email address that is not owned by anyone.']);

    Notification::assertNothingSent();
    $this->assertDatabaseCount('email_address_updates', 0);
});

test('Should successfully make a request to update email address', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.email'), [
            'email' => 'johndoe@email.com',
            'password' => 'P@ssword123'
        ])
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully made a request.',
        ]);

    Notification::assertSentTo(
        $this->user,
        SendVerificationCode::class,
        fn($notification, $channels) => $channels === ['mail']
    );

    $this->assertDatabaseCount('email_address_updates', 1);
    $this->assertDatabaseHas('email_address_updates', [
        'user_id' => $this->user->id
    ]);
});

test('Should throw an error if the verification code doesn\'t exist', function() {
    Notification::fake();

    $this->response
        ->putJson(route('settings.update.email'), [
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

test('Should throw an error for attempting to update email address with expired verification code', function() {
    $update = DB::table('email_address_updates')
                ->where('user_id', $this->user->id)
                ->whereNull('completed_at');
    
    $update->update(['expiration' => now()->subMinutes(40)]);

    Notification::fake();

    $this->response
        ->putJson(route('settings.update.email'), [
            'code' => $update->first()->code
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'code' => ['Invalid verification code.']
            ]
        ]);

    Notification::assertNothingSent();

    $this->assertDatabaseMissing('users', [
        'email' => $update->first()->data
    ]);
});

test('Should successfully update the emal address', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.email'), [
            'email' => 'johndoe@email.com',
            'password' => 'P@ssword123'
        ])
        ->assertOk();

    Notification::assertSentTo($this->user, SendVerificationCode::class);

    $code = DB::table('email_address_updates')
                ->where('user_id', $this->user->id)
                ->whereNull('completed_at')
                ->first()->code;

    $this->response
        ->putJson(route('settings.update.email'), compact('code'))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Update successful.',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'johndoe@email.com',
    ]);
});
