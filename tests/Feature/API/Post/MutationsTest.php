<?php

use App\Models\{User, Post};
use Illuminate\Support\Facades\{DB, Notification};
use App\Notifications\NotifyUponAction;

beforeAll(function() {
    User::factory(5)->hasPosts(5)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should not create a post if body is blank or not set', function () {
    $this->response
        ->postJson(route('posts.store'))
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Should not be blank.']);
});

test('Should not create a post if body length is greater than maximum length', function () {
    $this->response
        ->postJson(route('posts.store'), [
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['The number of characters exceeds the maximum length.']);
});

test('Should successfully create a post', function() {
    $this->response
        ->postJson(route('posts.store'), [
            'body' => 'Sample post'
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
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
            'status',
        ]);
});

test('Should successfully update a post', function() {
    $slug = $this->user->posts()->first()->slug;

    $this->response
        ->putJson(route('posts.update', ['post' => $slug]), [
            'body' => 'Hello World'
        ])
        ->assertOk();

    $this->assertDatabaseHas('posts', [
        'body' => 'Hello World'
    ]);
});

test('Should not be able to update other user\'s post', function() {
    $user = User::firstWhere('id', '!=', $this->user->id);
    $slug = $user->posts()->first()->slug;

    $this->response
        ->putJson(route('posts.update', ['post' => $slug]), [
            'body' => 'Hello World'
        ])
        ->assertForbidden();
    
    $this->assertDatabaseMissing('posts', [
        'user_id' => $user->id,
        'body' => 'Hello World',
    ]);
});

test('Should successfully delete a post', function() {
    $slug = $this->user->posts()->first()->slug;

    $this->response
        ->deleteJson(route('posts.destroy', ['post' => $slug]))
        ->assertOk();

    $this->assertDatabaseMissing('posts', compact('slug'));
});

test('Should not be able to delete other user\'s post', function() {
    $user = User::firstWhere('id', '!=', $this->user->id);
    $slug = $user->posts()->first()->slug;

    $this->response
        ->deleteJson(route('posts.destroy', ['post' => $slug]))
        ->assertForbidden();

    $this->assertDatabaseHas('posts', compact('slug'));
});

test('Should be able to like a post', function() {
    $post = Post::firstWhere('user_id', '!=', $this->user->id);
    
    Notification::fake();

    $this->response
        ->postJson(route('posts.like', ['post' => $post->slug]))
        ->assertOk();

    Notification::assertSentTo(
        $post->user,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->action === config('api.notifications.post_liked')
        )
    );

    $this->assertTrue($this->user->likedPosts()->where('id', $post->id)->exists());
});

test('Should not be able to like a post that has already been liked', function() {
    $post = Post::firstWhere('user_id', '!=', $this->user->id);

    Notification::fake();

    // Suppose the user has already liked the selected post based from the test above.
    $this->response
        ->postJson(route('posts.like', ['post' => $post->slug]))
        ->assertForbidden();

    Notification::assertNothingSent();

    $this->assertDatabaseCount('likables', 1);
});

test('Should be able to dislike a post', function() {
    $post = Post::firstWhere('user_id', '!=', $this->user->id);

    // Suppost the selected post has already been liked based on the test above.
    $this->response
        ->deleteJson(route('posts.dislike', ['post' => $post->slug]))
        ->assertOk();

    $this->assertFalse($this->user->likedPosts()->where('id', $post->id)->exists());
});

test('Should not be able to dislike a post that is not liked', function() {
    $post = Post::firstWhere('user_id', '!=', $this->user->id);

    $this->response
        ->deleteJson(route('posts.dislike', ['post' => $post->slug]))
        ->assertForbidden();

    $this->assertFalse($this->user->likedPosts()->where('id', $post->id)->exists());
});

test('Should be able to bookmark a post', function() {
    $post = Post::firstWhere('user_id', '!=', $this->user->id);

    $this->response
        ->postJson(route('posts.bookmark', ['post' => $post->slug]))
        ->assertOk();

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $post->id
    ]);
});

test('Should not be able to bookmark a post that has already been bookmarked', function() {
    $post = Post::firstWhere('user_id', '!=', $this->user->id);

    // Suppost the selected post has already been bookmarked based on the test above.
    $this->response
        ->postJson(route('posts.bookmark', ['post' => $post->slug]))
        ->assertForbidden();

    $this->assertDatabaseCount('bookmarks', 1);
});

test('Should be able to unbookmark a post', function() {
    $post = Post::firstWhere('user_id', '!=', $this->user->id);

    // Suppost the selected post has already been bookmarked based on the test above.
    $this->response
        ->deleteJson(route('posts.unbookmark', ['post' => $post->slug]))
        ->assertOk();

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $post->id
    ]);
});

test('Should not be able to unbookmark a post that is not bookmarked', function() {
    $post = Post::firstWhere('user_id', '!=', $this->user->id);

    $this->response
        ->deleteJson(route('posts.unbookmark', ['post' => $post->slug]))
        ->assertForbidden();

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $post->id
    ]);
});