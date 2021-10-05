<?php

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\{DB, Event};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
    DB::table('personal_access_tokens')->truncate();
});

test('Should return an auth token if successful', function() {
    Event::fake([Login::class]);

    $user = User::factory()->create();
    
    $this->postJson(route('auth.login'), [
        'username' => $user->username,
        'password' => 'P@ssword123'
    ])->assertOk();

    $this->assertTrue($user->tokens()->count() === 1);

    $this->actingAs($user)
        ->postJson(route('auth.logout'))
        ->assertOk();

    $this->assertTrue($user->tokens()->count() === 0);
});