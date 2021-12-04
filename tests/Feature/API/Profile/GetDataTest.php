<?php

use App\Models\{User, Post, Comment};
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;

$userBody = ['slug', 'name', 'username', 'gender', 'image_url'];

$miscBody = ['has_more', 'next_offset', 'status'];

$postBody = [
    'slug',
    'body',
    'likes_count',
    'comments_count',
    'timestamp',
    'is_own_post',
    'is_liked',
    'is_edited',
    'is_bookmarked',
    'user' => $userBody,
];

$commentBody = [
    'slug',
    'body',
    'is_own_comment',
    'is_liked',
    'is_edited',
    'timestamp',
    'user' => $userBody,
];

uses(WithFaker::class);

beforeAll(function() {
    User::factory(60)->hasPosts(5)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('comments')->truncate();
});

test('The visited user profile is not self', function() {
    $username = User::firstWhere('id', '!=', $this->user->id)->username;

    $this->response
        ->getJson(route('profile.get.info', ['user' => $username]))
        ->assertOk()
        ->assertJsonPath('data.is_self', false);
});

test('The visited user profile is self', function() {
    $this->response
        ->getJson(route('profile.get.info', ['user' => $this->user->username]))
        ->assertOk()
        ->assertJsonPath('data.is_self', true);
});

test('Should return the profile data with followers count and following count', function() {
    // Assume that the auth user is already following 40 users.
    $followingIds = User::where('id', '!=', $this->user->id)->inRandomOrder()->limit(40)->pluck('id');
    $followerIds = User::where('id', '!=', $this->user->id)->inRandomOrder()->limit(5)->pluck('id');

    $this->user->following()->sync($followingIds);
    $this->user->followers()->sync($followerIds);

    $this->response
        ->getJson(route('profile.get.info', ['user' => $this->user->username]))
        ->assertOk()
        ->assertJsonPath('data.followers_count', 5)
        ->assertJsonPath('data.following_count', 40);

    $this->user->following()->detach();
    $this->user->followers()->detach();
});

test('Should return the paginated list of owned posts', function() use ($postBody, $miscBody) {
    $this->response
        ->getJson(route('profile.get.posts', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(5, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure(array_merge($miscBody, [
            'items' => ['*' => $postBody]
        ]));

    $this->response
        ->getJson(route('profile.get.posts', [
            'user' => $this->user->username,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of liked posts', function() use ($postBody, $miscBody) {
    $likedIds = Post::inRandomOrder()->limit(5)->pluck('id');

    $this->user->likedPosts()->sync($likedIds);

    $this->response
        ->getJson(route('profile.likes.posts', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(5, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure(array_merge($miscBody, [
            'items' => ['*' => $postBody]
        ]));

    $this->response
        ->getJson(route('profile.likes.posts', [
            'user' => $this->user->username,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of liked comments', function() use ($commentBody, $miscBody) {
    $likedIds = Comment::factory(5)
                    ->for(User::firstWhere('id', '!=', $this->user->id))
                    ->for(Post::first())
                    ->create()
                    ->pluck('id');

    $this->user->likedComments()->sync($likedIds);

    $this->response
        ->getJson(route('profile.likes.comments', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(5, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure(array_merge($miscBody, [
            'items' => ['*' => $commentBody]
        ]));

    $this->response
        ->getJson(route('profile.likes.comments', [
            'user' => $this->user->username,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of comments', function() use ($userBody, $postBody, $commentBody) {
    Comment::factory(7)
        ->for($this->user)
        ->for(Post::first())
        ->create();

    $this->response
        ->getJson(route('profile.get.comments', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(7, 'items')
        ->assertJsonStructure([
            'items' => ['*' => $commentBody],
            'has_more',
            'next_offset'
        ]);
});

test('Should return the paginated list of bookmarked posts', function() use ($postBody, $miscBody) {
    $bookmarkedIds = Post::inRandomOrder()->limit(5)->pluck('id');

    $this->user->bookmarks()->sync($bookmarkedIds);

    $this->response
        ->getJson(route('profile.bookmarks', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(5, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null)
        ->assertJsonStructure(array_merge($miscBody, [
            'items' => ['*' => $postBody]
        ]));

    $this->response
        ->getJson(route('profile.bookmarks', [
            'user' => $this->user->username,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of followed users', function() {
    $followingIds = User::where('id', '!=', $this->user->id)->inRandomOrder()->limit(20)->pluck('id');

    $this->user->following()->sync($followingIds);

    // First full-page scroll
    $this->response
        ->getJson(route('profile.get.following', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    // The last full-page scroll that returns an empty list
    $this->response
        ->getJson(route('profile.get.following', [
            'user' => $this->user->username,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of followers', function() {
    $followerIds = User::where('id', '!=', $this->user->id)->inRandomOrder()->limit(20)->pluck('id');

    $this->user->followers()->sync($followerIds);

    // First full-page scroll
    $this->response
        ->getJson(route('profile.get.followers', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    // The last full-page scroll that returns an empty list
    $this->response
        ->getJson(route('profile.get.followers', [
            'user' => $this->user->username,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return an error if the visited user profile doesn\'t exist', function() {
    $this->response
        ->getJson(route('profile.get.info', ['user' => 'foobar']))
        ->assertNotFound();
});
