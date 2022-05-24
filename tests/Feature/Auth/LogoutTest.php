<?php

use App\Models\User;

test('Should return an auth token if successful', function() {
    $user = User::factory()->create();
    $data = [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ];
    
    $this->postJson(route('auth.login'), $data)->assertOk();

    $this->assertTrue($user->tokens()->count() === 1);

    $this->actingAs($user)
        ->postJson(route('auth.logout'))
        ->assertOk();

    $this->assertTrue($user->tokens()->count() === 0);
});
