<?php

use App\Models\User;

test("Should throw an error if the entered credentials don't exist", function() {
    $data = [
        'username' => 'username',
        'password' => 'password'
    ];
    
    $this->postJson(route('auth.login'), $data)->assertNotFound();
});

test('Should throw an error if a user is not yet verified', function() {
    $user = User::factory()->unverified()->create();
    $data = [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ];

    $this->postJson(route('auth.login'), $data)
        ->assertUnauthorized();
});

test('Should return an auth token if successful', function() {
    $user = User::factory()->create();
    $data = [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ];

    $this->postJson(route('auth.login'), $data)
        ->assertOk()
        ->assertJsonStructure(['token', 'message']);
});
