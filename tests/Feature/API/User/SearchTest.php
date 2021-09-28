<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(5)->sequence(
        fn($sequence) => [
            'name' => "Dummy User {$sequence->index}",
            'username' => "sampleuser00{$sequence->index}",
        ]
    )->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw a validation error if search query is not set or null', function() {
    $this->response
        ->getJson(route('users.search'))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully retrieved data.',
            'data' => [],
        ]);
});

test('Should return empty list if user doesn\'t exist', function() {
    $this->response
        ->getJson(route('users.search', ['query' => 'unknownuser']))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully retrieved data.',
            'data' => [],
        ]);
});

test('Searching by name should return list of users', function() {
    $this->response
        ->getJson(route('users.search', ['query' => 'dummy user']))
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

test('Searching by username should return list of users', function() {
    $this->response
        ->getJson(route('users.search', ['query' => 'sampleuser0']))
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

test('Should return paginated list of users with search query', function() {
    $this->response
        ->getJson(route('users.get', ['query' => 'dummy user', 'page' => 1]))
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson(route('users.get', ['query' => 'dummy user', 'page' => 2]))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});