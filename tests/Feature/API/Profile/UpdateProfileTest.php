<?php

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

uses(WithFaker::class);

beforeEach(function() {
    $this->user = User::factory()->create();

    authenticate();
});

test('Should throw validation errors if input values are incorrect in update profile form', function() {
    $data = [
        'bio' => $this->faker->paragraphs(5, true)
    ];

    $this->putJson(route('profile.update'), $data)
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
    $data = [
        'name' => 'John Doe',
        'bio' => 'Hello World',
        'birth_date' => '1995-05-05',
    ];

    $this->putJson(route('profile.update'), $data)
        ->assertOk();
   
    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'name' => 'John Doe',
        'bio' => 'Hello World',
        'birth_date' => Carbon::create(1995, 5, 5),
    ]);
});
