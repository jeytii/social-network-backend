<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Cache};

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('jobs')->truncate();
});

beforeEach(function() {
    $this->token = bin2hex(random_bytes(16));
    $this->cacheKey = "verification.{$this->token}";
    $this->user = User::factory()->create([
        'email_verified_at' => null
    ]);
});

afterEach(function() {
    DB::table('users')->truncate();
});

test('Should throw an error for entering an incorrect verification code', function() {
    Cache::shouldReceive('get')
        ->once()
        ->with($this->cacheKey)
        ->andReturn([
            'id' => $this->user->id,
            'code' => '123456'
        ]);

    Cache::shouldReceive('has')->once()->with($this->cacheKey)->andReturnTrue();

    $this->putJson(route('auth.verify'), [
        'code' => '012345',
        'token' => $this->token,
    ])->assertUnauthorized();
});

test('Should successfully verify account', function() {
    Cache::shouldReceive('get')
        ->once()
        ->with($this->cacheKey)
        ->andReturn([
            'id' => $this->user->id,
            'code' => '123456'
        ]);

    Cache::shouldReceive('has')->once()->with($this->cacheKey)->andReturnTrue();
    Cache::shouldReceive('forget')->once()->with($this->cacheKey);

    $this->putJson(route('auth.verify'), [
        'code' => '123456',
        'token' => $this->token,
    ])->assertOk();

    $user = User::first();

    $this->assertTrue($user->hasVerifiedEmail());
});