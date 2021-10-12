<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeAll(function() {
    User::factory(10)->hasPosts(5)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should return the paginated list of posts from followed users', function() {
    $followingIds = User::where('id', '!=', $this->user->id)->inRandomOrder()->limit(5)->pluck('id');

    $this->user->following()->sync($followingIds);
    
    $this->response
        ->getJson(route('posts.index'))
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
                    'user' => config('api.response.user.basic')
                ],
            ],
            'has_more',
            'next_offset',
            'status',
        ]);

    $this->response
        ->getJson(route('posts.index', ['page' => 2]))
        ->assertOk()
        ->assertJsonCount(10, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson(route('posts.index', ['page' => 3]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should sort posts by number of likes', function() {
    $first = $this->user->following()->first();
    $second = $this->user->following()->firstWhere('id', '!=', $first->id);

    $firstMostLiked = $first->posts()->first();
    $secondMostLiked = $second->posts()->first();

    $firstMostLiked->likers()->sync(User::inRandomOrder()->limit(10)->pluck('id'));
    $secondMostLiked->likers()->sync(User::inRandomOrder()->limit(5)->pluck('id'));

    $this->response
        ->getJson(route('posts.index', ['sort' => 'likes']))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2)
        ->assertJson([
            'items' => [
                [
                    'slug' => $firstMostLiked->slug,
                    'body' => $firstMostLiked->body,
                    'likes_count' => 10,
                ],
                [
                    'slug' => $secondMostLiked->slug,
                    'body' => $secondMostLiked->body,
                    'likes_count' => 5,
                ],
            ]
        ]);

    $this->response
        ->getJson(route('posts.index', ['sort' => 'likes', 'page' => 2]))
        ->assertOk()
        ->assertJsonCount(10, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson(route('posts.index', ['sort' => 'likes', 'page' => 3]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});
