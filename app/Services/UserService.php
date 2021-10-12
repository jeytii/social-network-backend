<?php

namespace App\Services;

use App\Models\{User, Notification};
use Illuminate\Support\Facades\DB;
use App\Notifications\NotifyUponAction;
use Exception;

class UserService
{
    /**
     * Add a user model to the list of followed users.
     * 
     * @param \App\Models\User  $follower
     * @param \App\Models\User  $followedUser
     * @return array
     * @throws \Exception
     */
    public function follow(User $follower, User $followedUser): array
    {
        try {
            DB::transaction(function() use ($follower, $followedUser) {
                $follower->following()->sync([$followedUser->id]);

                $followedUser->notify(new NotifyUponAction(
                    $follower,
                    Notification::FOLLOWED,
                    "/{$followedUser->username}"
                ));
            });

            return ['status' => 200];
        }
        catch (Exception $exception) {
            return [
                'status' => 500,
                'message' => 'Something went wrong. Please check your connection then try again.',
            ];
        }
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