<?php

use App\Models\User;

beforeEach(function() {
    User::factory(10)->hasPosts(5)->create();

    $this->user = User::first();

    authenticate();
});

test('Should return the paginated list of own posts and posts from followed users', function() {
    $followingIds = User::whereKeyNot($this->user->id)->inRandomOrder()->limit(5)->pluck('id');

    $this->user->following()->sync($followingIds);
    
    $this->getJson(route('posts.index'))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'items' => [
                '*' => [
                    'slug',
                    'body',
                    'likes_count',
                    'comments_count',
                    'timestamp',
                    'is_own_post',
                    'is_liked',
                    'is_edited',
                    'is_bookmarked',
                    'user' => config('response.user')
                ],
            ],
            'has_more',
            'next_offset',
        ]);

    $this->getJson(route('posts.index', ['page' => 2]))
        ->assertOk()
        ->assertJsonCount(10, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->getJson(route('posts.index', ['page' => 3]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
