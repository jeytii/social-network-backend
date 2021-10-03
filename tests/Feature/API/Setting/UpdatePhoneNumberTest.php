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

test('Should throw 422 errors in requesting to update the phone number', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.phone-number'), [
            'password' => 'password123'
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'phone_number' => ['The phone number field is required.'],
                'password' => ['Incorrect password.'],
            ]
        ]);

    Notification::assertNothingSent();
    $this->assertDatabaseCount('phone_number_updates', 0);
});

test('Should throw an error for entering the current phone number', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.phone-number'), [
            'phone_number' => $this->user->phone_number,
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.phone_number', ['Someone has already taken that phone number.']);

    Notification::assertNothingSent();
    $this->assertDatabaseCount('phone_number_updates', 0);
});

test('Should successfully make a request to update phone number', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.phone-number'), [
            'phone_number' => '09123456789',
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
        fn($notification, $channels) => $channels === ['nexmo']
    );

    $this->assertDatabaseCount('phone_number_updates', 1);
    $this->assertDatabaseHas('phone_number_updates', [
        'user_id' => $this->user->id
    ]);
});

test('Should throw an error if the verification code doesn\'t exist', function() {
    Notification::fake();

    $this->response
        ->putJson(route('settings.update.phone-number'), [
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

test('Should throw an error for attempting to update phone number with expired verification code', function() {
    $update = DB::table('phone_number_updates')
                ->where('user_id', $this->user->id)
                ->whereNull('completed_at');
    
    $update->update(['expiration' => now()->subMinutes(40)]);

    Notification::fake();

    $this->response
        ->putJson(route('settings.update.phone-number'), [
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
        'phone_number' => $update->first()->data
    ]);
});

test('Should successfully update the phone number', function() {
    Notification::fake();

    $this->response
        ->postJson(route('settings.request-update.phone-number'), [
            'phone_number' => '09123456789',
            'password' => 'P@ssword123'
        ])
        ->assertOk();

    Notification::assertSentTo($this->user, SendVerificationCode::class);

    $code = DB::table('phone_number_updates')
                ->where('user_id', $this->user->id)
                ->whereNull('completed_at')
                ->first()->code;

    $this->response
        ->putJson(route('settings.update.phone-number'), compact('code'))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Update successful.',
        ]);

    $this->assertDatabaseHas('users', [
        'phone_number' => '639123456789',
    ]);
});
