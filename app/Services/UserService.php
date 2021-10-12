<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NotifyUponAction;

class UserService
{
    /**
     * Add a user model to the list of followed users.
     * 
     * @param \App\Models\User  $follower
     * @param \App\Models\User  $followedUser
     * @return array
     */
    public function follow(User $follower, User $followedUser): array
    {
        $follower->following()->sync([$followedUser->id]);

        $followedUser->notify(new NotifyUponAction(
            $follower,
            config('constants.notifications.user_followed'),
            "/{$followedUser->username}"
        ));

        return ['status' => 200];
    }

    /**
     * Remove a user model from the list of followed users.
     * 
     * @param \App\Models\User  $follower
     * @param \App\Models\User  $followedUser
     * @return array
     */
    public function unfollow(User $follower, User $unfollowedUser): array
    {
        $follower->following()->detach($unfollowedUser->id);

        return ['status' => 200];
    }
}