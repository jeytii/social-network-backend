<?php

use App\Models\{User, Post, Comment};

beforeEach(function() {
    User::factory(3)->hasPosts(3)->create();
    
    Comment::factory(35)
        ->for(User::first())
        ->for(Post::first())
        ->create();

    authenticate();
});

test('Should return a paginated list of comments under a specific post', function() {
    $slug = Post::first()->slug;
    
    $this->getJson(route('comments.index', ['post' => $slug, 'page' => 1]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJsonStructure([
            'items' => [
                '*' => [
                    'slug',
                    'post_slug',
                    'body',
                    'is_own_comment',
                    'is_liked',
                    'is_edited',
                    'timestamp',
                    'user' => config('response.user')
                ]
            ],
            'has_more',
            'next_offset',
        ]);

    $this->getJson(route('comments.index', ['post' => $slug, 'page' => 2]))
        ->assertOk()
        ->assertJsonCount(15, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->getJson(route('comments.index', ['post' => $slug, 'page' => 3]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
