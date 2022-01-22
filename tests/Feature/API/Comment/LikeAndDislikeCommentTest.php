<?php

use App\Models\{User, Comment, Notification as NotificationModel};
use App\Notifications\NotifyUponAction;
use Illuminate\Support\Facades\{DB, Notification, Cache};

beforeAll(function() {
    User::factory(2)->hasPosts(2)->hasComments(2)->create();
});

afterAll(function() {
    (new self(function() {}, '', []))->setUp();

    DB::table('users')->truncate();
    DB::table('posts')->truncate();
    DB::table('comments')->truncate();
    DB::table('jobs')->truncate();
    Cache::flush();
});

test('Should be able to like a comment', function() {
    $comment = Comment::firstWhere('user_id', '!=', $this->user->id);
    
    Notification::fake();

    $this->response
        ->postJson(route('comments.like', ['comment' => $comment->slug]))
        ->assertOk();

    Notification::assertSentTo(
        $comment->user,
        NotifyUponAction::class,
        fn($notification) => (
            $notification->action === NotificationModel::LIKED_COMMENT
        )
    );

    $this->assertTrue($this->user->likedComments()->whereKey($comment->id)->exists());
});

test('Should not be able to like a comment that has already been liked', function() {
    $comment = DB::table('comments')->where('user_id', '!=', $this->user->id)->first()->slug;

    Notification::fake();

    // Suppose the user has already liked the selected comment based from the test above.
    $this->response
        ->postJson(route('comments.like', compact('comment')))
        ->assertForbidden();

    Notification::assertNothingSent();

    $this->assertDatabaseCount('likables', 1);
});

test('Should be able to dislike a comment', function() {
    $comment = DB::table('comments')->where('user_id', '!=', $this->user->id)->first();

    // Suppose the selected comment has already been liked based on the test above.
    $this->response
        ->deleteJson(route('comments.dislike', ['comment' => $comment->slug]))
        ->assertOk();

    $this->assertTrue($this->user->likedComments()->whereKey($comment->id)->doesntExist());
});

test('Should not be able to dislike a comment that is not liked', function() {
    $comment = DB::table('comments')->where('user_id', '!=', $this->user->id)->first();

    $this->response
        ->deleteJson(route('comments.dislike', ['comment' => $comment->slug]))
        ->assertForbidden();

    $this->assertTrue($this->user->likedComments()->whereKey($comment->id)->doesntExist());
});
