<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(5)->sequence(
        fn($sequence) => [
            'name' => "Dummy User {$sequence->index}"
        ]
    )
    ->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw a validation error if search query is not set or null', function() {
    $this->response
        ->getJson('/api/users/search')
        ->assertStatus(422);
});

test('Should return empty list if user doesn\'t exist', function() {
    $this->response
        ->getJson('/api/users/search?query=unknownuser')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('Searching by name should return list of users', function() {
    $this->response
        ->getJson('/api/users/search?query=dummy%20user')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

test('Searching by username should return list of users', function() {
    User::factory(3)
        ->sequence(
            fn($sequence) => ['username' => 'sampleuser00' . $sequence->index]
        )
        ->create();

    $this->response
        ->getJson('/api/users/search?query=sampleuser0')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('Should return paginated list of users with search query', function() {
    $this->response
        ->getJson('/api/users?page=1&search=dummy%20user')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson('/api/users?page=2&search=dummy%20user')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});