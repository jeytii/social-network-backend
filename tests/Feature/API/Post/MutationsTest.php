<?php

use App\Models\{User, Post};
use Illuminate\Support\Facades\{DB, Notification};
use App\Notifications\NotifyUponAction;

beforeAll(function() {
    User::factory(5)->hasPosts(5)->create();
});

beforeEach(function() {
    $this->post = Post::find(10);
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    DB::table('users')->truncate();
});

test('Should not create a post if body is blank or not set', function () {
    $this->response
        ->postJson(route('posts.create'))
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Should not be blank.']);
});

test('Should not create a post if body length is greater than maximum length', function () {
    $this->response
        ->postJson(route('posts.create'), [
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud quis nostrud quis nostrud.'
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.body', ['Maximum character length is 180.']);
});

test('Should successfully create a post', function() {
    $this->response
        ->postJson(route('posts.create'), [
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
                'user' => array_merge(config('api.response.user.basic'), ['slug'])
            ],
            'status',
            'message',
        ]);
});

test('Should successfully update a post', function() {
    $slug = $this->user->posts()->first()->slug;

    $this->response
        ->putJson(route('posts.update', ['post' => $slug]), [
            'body' => 'Hello World'
        ])
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully updated a post.',
        ]);

    $this->assertDatabaseHas('posts', [
        'body' => 'Hello World'
    ]);
});

test('Should not be able to update other user\'s post', function() {
    $slug = User::find(3)->posts()->first()->slug;

    $this->response
        ->putJson(route('posts.update', ['post' => $slug]), [
            'body' => 'Hello World'
        ])
        ->assertForbidden();
    
    $this->assertDatabaseMissing('posts', [
        'user_id' => 3,
        'body' => 'Hello World',
    ]);
});

test('Should successfully delete a post', function() {
    $slug = $this->user->posts()->first()->slug;

    $this->response
        ->deleteJson(route('posts.delete', ['post' => $slug]))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully deleted a post.',
        ]);

    $this->assertDatabaseMissing('posts', compact('slug'));
});

test('Should not be able to delete other user\'s post', function() {
    $slug = User::find(3)->posts()->first()->slug;

    $this->response
        ->deleteJson("/api/posts/$slug")
        ->assertForbidden();

    $this->assertDatabaseHas('posts', compact('slug'));
});

test('Should be able to like a post', function() {
    Notification::fake();

    $this->response
        ->postJson(route('posts.like', ['post' => $this->post->slug]))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully liked a post.',
        ]);

    Notification::assertSentTo(
        $this->post->user,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->user->id === $this->user->id &&
            $notification->actionType === config('api.notifications.post_liked')
        )
    );

    $this->assertDatabaseHas('likes', [
        'user_id' => $this->user->id,
        'post_id' => $this->post->id
    ]);
});

test('Should not be able to like a post that has already been liked', function() {
    Notification::fake();

    // Suppose the user has already liked the selected post based from the test above.
    $this->response
        ->postJson(route('posts.like', ['post' => $this->post->slug]))
        ->assertForbidden();

    Notification::assertNothingSent();

    $this->assertDatabaseCount('likes', 1);
});

test('Should be able to dislike a post', function() {
    // Suppost the selected post has already been liked based on the test above.
    $this->response
        ->deleteJson(route('posts.dislike', ['post' => $this->post->slug]))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully disliked a post.',
        ]);

    $this->assertDatabaseMissing('likes', [
        'user_id' => $this->user->id,
        'post_id' => $this->post->id
    ]);
});

test('Should not be able to dislike a post that is not liked', function() {
    $this->response
        ->deleteJson(route('posts.dislike', ['post' => $this->post->slug]))
        ->assertForbidden();

    $this->assertDatabaseMissing('likes', [
        'user_id' => $this->user->id,
        'post_id' => $this->post->id
    ]);
});

test('Should be able to bookmark a post', function() {
    $this->response
        ->postJson(route('posts.bookmark', ['post' => $this->post->slug]))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully bookmarked a post.',
        ]);

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $this->post->id
    ]);
});

test('Should not be able to bookmark a post that has already been bookmarked', function() {
    // Suppost the selected post has already been bookmarked based on the test above.
    $this->response
        ->postJson(route('posts.bookmark', ['post' => $this->post->slug]))
        ->assertForbidden();

    $this->assertDatabaseCount('bookmarks', 1);
});

test('Should be able to unbookmark a post', function() {
    // Suppost the selected post has already been bookmarked based on the test above.
    $this->response
        ->deleteJson(route('posts.unbookmark', ['post' => $this->post->slug]))
        ->assertOk()
        ->assertExactJson([
            'status' => 200,
            'message' => 'Successfully unbookmarked a post.',
        ]);

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $this->post->id
    ]);
});

test('Should not be able to unbookmark a post that is not bookmarked', function() {
    $this->response
        ->deleteJson(route('posts.unbookmark', ['post' => $this->post->slug]))
        ->assertForbidden();

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $this->user->id,
        'bookmark_id' => $this->post->id
    ]);
});