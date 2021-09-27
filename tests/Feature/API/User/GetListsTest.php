<?php

namespace Tests\Feature\API\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(50)->create();
});

beforeEach(function() {
    $this->columns = array_merge(
        config('api.response.user.basic'),
        ['slug', 'is_followed', 'is_self']
    );
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should return paginated list of users', function() {
    // First scroll full-page bottom
    $this->response
        ->getJson('/api/users?page=1')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'data' => [
                '*' => $this->columns,
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
        ->getJson('/api/users?page=3')
        ->assertOk()
        ->assertJsonCount(9, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    // Full-page scroll attempt but should return empty list
    $this->response
        ->getJson('/api/users?page=4')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should successfully return 3 suggested users', function() {
    $this->response
        ->getJson('/api/users/suggested')
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => $this->columns,
            ],
        ]);
});