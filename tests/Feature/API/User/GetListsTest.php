<?php

namespace Tests\Feature\API\User;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

beforeEach(function() {
    $user = User::factory()->create();

    $this->response = $this->actingAs($user);

    Sanctum::actingAs($user, ['*']);
});

afterEach(function() {
    DB::table('users')->truncate();
});

test('Should return paginated list of users', function() {
    User::factory(100)->create();

    // First scroll full-page bottom
    $this->response
        ->getJson('/api/users?page=1')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'data' => [
                '*' => ['slug', 'name', 'username', 'gender', 'image_url'],
            ],
        ]);

    // Second scroll full-page bottom
    $this->response
        ->getJson('/api/users?page=2')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 3);

    // The last full-page scroll that returns data
    $this->response
        ->getJson('/api/users?page=5')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    // Full-page scroll attempt but should return empty list
    $this->response
        ->getJson('/api/users?page=6')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should successfully return 3 suggested users', function() {
    User::factory(10)->create();

    $this->response
        ->getJson('/api/users/suggested')
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['slug', 'name', 'username', 'gender', 'image_url'],
            ],
        ]);
});