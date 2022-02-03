<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('personal_access_tokens')->truncate();
});

test("Should throw an error if the entered credentials don't exist", function() {
    $this->postJson(route('auth.login'), [
        'username' => 'username',
        'password' => 'password'
    ])->assertNotFound();
});

test('Should throw an error if a user is not yet verified', function() {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->postJson(route('auth.login'), [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])->assertUnauthorized();
});

test('Should return an auth token if successful', function() {
    $user = User::factory()->create();

    $this->postJson(route('auth.login'), [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])
    ->assertOk()
    ->assertJsonStructure(['token', 'message']);
});
