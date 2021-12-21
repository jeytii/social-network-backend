<?php

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

uses(WithFaker::class);

beforeAll(function() {
    User::factory()->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
});

test('Should throw validation errors if input values are incorrect in update profile form', function() {
    $this->response
        ->putJson(route('profile.update'), [
            'bio' => $this->faker->paragraphs(5, true)
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'name' => ['Name is required.'],
                'birth_date' => ['Birth date is required.'],
                'bio' => ['The number of characters exceeds the maximum length.'],
            ]
        ]);
});

test('Can update the profile successfully', function() {
    $this->response
        ->putJson(route('profile.update'), [
            'name' => 'John Doe',
            'bio' => 'Hello World',
            'birth_date' => '1995-05-05',
        ])
        ->assertOk();
   
    $updatedUser = DB::table('users')->where([
        ['id', '=', $this->user->id],
        ['name', '=', 'John Doe'],
        ['bio', '=', 'Hello World'],
        ['birth_date', '=', Carbon::create(1995, 5, 5)],
    ]);

    $this->assertTrue($updatedUser->exists());
});
