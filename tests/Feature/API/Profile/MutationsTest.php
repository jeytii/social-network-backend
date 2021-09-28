<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;

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
                'name' => ['The name field is required.'],
                'bio' => ['The number of characters exceeds the maximum length.'],
            ]
        ]);
});

test('Can\'t update the birth date that has been already set', function() {
    $this->response
        ->putJson(route('profile.update'), [
            'name' => $this->user->name,
            'birth_month' => 'January',
            'birth_day' => 12,
            'birth_year' => 1996,
        ])
        ->assertOk();
   
    $updatedUser = User::find($this->user->id);

    $this->assertTrue($this->user->birth_date === $updatedUser->birth_date);
});

test('Can update the profile successfully', function() {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson(route('profile.update'), [
            'name' => 'John Doe',
            'location' => 'Philippines',
            'bio' => 'Hello World',
        ])
        ->assertOk();
   
    $updatedUser = User::where([
        ['id', '=', $user->id],
        ['name', '=', 'John Doe'],
        ['location', '=', 'Philippines'],
        ['bio', '=', 'Hello World'],
    ]);

    $this->assertTrue($updatedUser->exists());
});
