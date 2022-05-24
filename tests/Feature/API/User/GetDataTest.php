<?php

namespace Tests\Feature\API\User;

use App\Models\User;

beforeEach(function() {
    User::factory(50)->create();

    $this->columns = array_merge(config('response.user'), ['is_followed', 'is_self']);

    authenticate();
});

test('Should return paginated list of users', function() {
    // First scroll full-page bottom
    $this->getJson(route('users.index', ['page' => 1]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'items' => [
                '*' => $this->columns,
            ],
        ]);

    // Second scroll full-page bottom
    $this->getJson(route('users.index', ['page' => 2]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 3);

    // The last full-page scroll that returns data
    $this->getJson(route('users.index', ['page' => 3]))
        ->assertOk()
        ->assertJsonCount(9, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    // Full-page scroll attempt but should return empty list
    $this->getJson(route('users.index', ['page' => 4]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should successfully return 3 randomly suggested users', function() {
    $this->getJson(route('users.get.random'))
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => $this->columns,
            ],
        ]);
});