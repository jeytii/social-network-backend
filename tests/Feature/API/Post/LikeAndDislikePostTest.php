<?php

use App\Models\{User, Notification as NotificationModel};
use Illuminate\Support\Facades\{DB, Notification, Cache};
use App\Notifications\NotifyUponAction;

beforeAll(function() {
    User::factory(2)->hasPosts(2)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();
    
    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('notifications')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should be able to like a post', function() {
    $post = DB::table('posts')->where('user_id', '!=', $this->user->id)->first();
    $poster = User::firstWhere('id', $post->user_id);

    Notification::fake();

    $this->response
        ->postJson(route('posts.like', ['post' => $post->slug]))
        ->assertOk();

    Notification::assertSentTo(
        $poster,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->action === NotificationModel::LIKED_POST
        )
    );

    $this->assertTrue($this->user->likedPosts()->whereKey($post->id)->exists());
});

test('Should not be able to like a post that has already been liked', function() {
    $post = DB::table('posts')->where('user_id', '!=', $this->user->id)->first();

    Notification::fake();

    // Suppose the user has already liked the selected post based from the test above.
    $this->response
        ->postJson(route('posts.like', ['post' => $post->slug]))
        ->assertForbidden();

    Notification::assertNothingSent();

    $this->assertDatabaseCount('likables', 1);
});

test('Should be able to dislike a post', function() {
    $post = DB::table('posts')->where('user_id', '!=', $this->user->id)->first();

    // Suppost the selected post has already been liked based on the test above.
    $this->response
        ->deleteJson(route('posts.dislike', ['post' => $post->slug]))
        ->assertOk();

    $this->assertTrue($this->user->likedPosts()->whereKey($post->id)->doesntExist());
});

test('Should not be able to dislike a post that is not liked', function() {
    $post = DB::table('posts')->where('user_id', '!=', $this->user->id)->first();

    $this->response
        ->deleteJson(route('posts.dislike', ['post' => $post->slug]))
        ->assertForbidden();

    $this->assertTrue($this->user->likedPosts()->whereKey($post->id)->doesntExist());
});
