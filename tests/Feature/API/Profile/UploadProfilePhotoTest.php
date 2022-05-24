<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function() {
    $this->user = User::factory()->create();

    authenticate();
});

test('Should throw an error for uploading a non-image file', function() {
    Storage::fake();

    $file = UploadedFile::fake()->create('sample.pdf', 1500, 'application/pdf');

    $this->postJson(route('profile.upload.profile-photo'), ['image' => $file])
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

test('Should throw an error for uploading an image with out-of-range resolution', function() {
    Storage::fake();

    $image = UploadedFile::fake()->image('sample.jpg', 900, 900);

    $this->postJson(route('profile.upload.profile-photo'), compact('image'))
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'image' => ['Resolution must be from 100x100 to 800x800.']
            ]
        ]);
});

test('Should successfully upload a profile photo', function() {
    Storage::fake();

    $image = UploadedFile::fake()->image('sample.png', 200, 200);

    $this->postJson(route('profile.upload.profile-photo'), compact('image'))
        ->assertOk();
});