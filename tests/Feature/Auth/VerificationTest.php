<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function() {
    $this->token = bin2hex(random_bytes(16));
    $this->cacheKey = "verification.{$this->token}";
    $this->user = User::factory()->unverified()->create();
});

test('Should throw an error for entering an incorrect verification code', function() {
    $data = [
        'code' => '012345',
        'token' => $this->token,
    ];

    Cache::shouldReceive('get')
        ->once()
        ->with($this->cacheKey)
        ->andReturn([
            'id' => $this->user->id,
            'code' => '123456'
        ]);

    Cache::shouldReceive('has')
        ->once()
        ->with($this->cacheKey)
        ->andReturnTrue();

    $this->putJson(route('auth.verify'), $data)->assertUnauthorized();
});

test('Should successfully verify account', function() {
    $data = [
        'code' => '123456',
        'token' => $this->token,
    ];

    Cache::shouldReceive('get')
        ->once()
        ->with($this->cacheKey)
        ->andReturn([
            'id' => $this->user->id,
            'code' => '123456'
        ]);

    Cache::shouldReceive('has')
        ->once()
        ->with($this->cacheKey)
        ->andReturnTrue();

    Cache::shouldReceive('forget')
        ->once()
        ->with($this->cacheKey);

    $this->putJson(route('auth.verify'), $data)->assertOk();

    $this->assertTrue(User::first()->hasVerifiedEmail());
});