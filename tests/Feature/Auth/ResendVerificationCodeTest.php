<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Notification};

beforeAll(function() {
    User::factory(3)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw errors for non-existing username and invalid prefers_sms type', function() {
    Notification::fake();

    $this->postJson('/verify/resend', [
        'username' => 'invaliduser',
        'prefers_sms' => null,
    ])
        ->assertStatus(422);

    Notification::assertNothingSent();
});
