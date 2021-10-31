<?php

use App\Models\User;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\WithFaker;

uses(WithFaker::class);

beforeAll(function() {
    User::factory()->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should throw an error for uploading a non-image file', function() {
    Storage::fake();

    $this->response
        ->postJson(route('profile.upload.profile-photo'), [
            'image' => UploadedFile::fake()->create('sample.pdf', 1500, 'application/pdf')
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'image' => [
                    'Please upload an image file.',
                    'Resolution must be from 100x100 to 800x800.',
                ]
            ]
        ]);
});

test('Should throw an error for uploading an image with invalid', function() {
    Storage::fake();

    $this->response
        ->postJson(route('profile.upload.profile-photo'), [
            'image' => UploadedFile::fake()->image('sample.jpg', 900, 900)
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'image' => ['Resolution must be from 100x100 to 800x800.']
            ]
        ]);
});

test('Should successfully upload a profile photo', function() {
    Storage::fake();

    $this->response
        ->postJson(route('profile.upload.profile-photo'), [
            'image' => UploadedFile::fake()->image('sample.png', 200, 200)
        ])
        ->assertOk()
        ->assertJsonStructure(['status', 'data']);
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
                'bio' => ['The number of characters exceeds the maximum length.'],
            ]
        ]);
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
