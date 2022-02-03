<?php

use App\Models\{User, Post, Comment};
use Illuminate\Support\Facades\DB;

$userBody = ['slug', 'name', 'username', 'gender', 'image_url'];

$miscBody = ['has_more', 'next_offset'];

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

beforeAll(function() {
    User::factory(25)->hasPosts(5)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('comments')->truncate();
});

test('is_self should be false if the visited profile is not self', function() {
    $username = User::firstWhere('id', '!=', $this->user->id)->username;

    $this->response
        ->getJson(route('profile.get.info', ['user' => $username]))
        ->assertOk()
        ->assertJsonPath('data.is_self', false);
});

test('is_self should be true if the visited profile is self', function() {
    $this->response
        ->getJson(route('profile.get.info', ['user' => $this->user->username]))
        ->assertOk()
        ->assertJsonPath('data.is_self', true);
});

test('Should return an error if the visited user profile doesn\'t exist', function() {
    $this->response
        ->getJson(route('profile.get.info', ['user' => 'foobar']))
        ->assertNotFound();
});

test('Should return the profile data with number of followers and number of followed users', function() {
    $followingIds = DB::table('users')->where('id', '!=', $this->user->id)->inRandomOrder()->limit(21)->pluck('id');
    $followerIds = DB::table('users')->where('id', '!=', $this->user->id)->inRandomOrder()->limit(5)->pluck('id');

    $this->user->following()->sync($followingIds);
    $this->user->followers()->sync($followerIds);

    $this->response
        ->getJson(route('profile.get.info', ['user' => $this->user->username]))
        ->assertOk()
        ->assertJsonPath('data.followers_count', 5)
        ->assertJsonPath('data.following_count', 21);
});

test('Should return the paginated list of followed users', function() {
    $this->response
        ->getJson(route('profile.get.following', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(20, 'items')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_offset', 2);

    $this->response
        ->getJson(route('profile.get.following', [
            'user' => $this->user->username,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

    $this->response
        ->getJson(route('profile.get.following', [
            'user' => $this->user->username,
            'page' => 3,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);
});

test('Should return the paginated list of followers', function() {
    $this->response
        ->getJson(route('profile.get.followers', [
            'user' => $this->user->username,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(5, 'items')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_offset', null);

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
    $likedIds = DB::table('posts')->inRandomOrder()->limit(5)->pluck('id');

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

test('Should return the paginated list of comments', function() use ($commentBody) {
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
    $bookmarkedIds = DB::table('posts')->inRandomOrder()->limit(5)->pluck('id');

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
