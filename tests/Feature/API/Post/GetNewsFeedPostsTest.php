<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

beforeEach(function() {
    $this->user = User::factory()->create();
    $this->response = $this->actingAs($this->user);

    Sanctum::actingAs($this->user, ['*']);
});

afterEach(function() {
    DB::table('users')->truncate();
});

test('Should return the paginated list of posts from followed users', function() {
    User::factory(10)->hasPosts(5)->create();
    $this->user->following()->sync(range(2, 6));

    $this->response
        ->getJson('/api/posts')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'slug',
                    'body',
                    'likes_count',
                    'comments_count',
                    'timestamp',
                    'is_own_post',
                    'user' => [
                        'slug',
                        'name',
                        'username',
                        'gender',
                        'image_url'
                    ]
                ],
            ],
            'has_more',
            'next_offset'
        ]);;

    $this->response
        ->getJson('/api/posts?page=2')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson('/api/posts?page=3')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
