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
    $this->user->following()->sync(range(2, 6));
    
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
                    'user' => array_merge(config('api.response.user.basic'), ['slug'])
                ],
            ],
            'has_more',
            'next_offset',
            'status',
            'message',
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
    $firstMostLiked = User::find(2)->posts()->first();
    $secondMostLiked = User::find(3)->posts()->first();

    $firstMostLiked->likers()->sync(range(1, 10));
    $secondMostLiked->likers()->sync(range(1, 5));

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
