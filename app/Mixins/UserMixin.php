<?php

namespace App\Mixins;

class UserMixin
{
    /**
     * Find user by username or email address.
     * 
     * @return \Closure
     */
    public function whereUser()
    {
        return function(string $username) {
            return $this->where('username', $username)->orWhere('email', $username);
        };
    }

    /**
     * Search user by name or username.
     * 
     * @return \Closure
     */
    public function searchUser()
    {
        return function(string $query) {
            return $this->where('name', 'ilike', "%$query%")->orWhere('username', 'like', "%$query%");
        };
    }

    /**
     * Include the user with specific columns.
     * 
     * @return \Closure
     */
    public function withUser()
    {
        return function() {
            return $this->with('user:id,slug,' . join(',', config('api.response.user.basic')));
        };
    }

    /**
     * Get the first model with basic info only.
     * 
     * @return \Closure
     */
    public function firstWithBasicOnly()
    {
        return function() {
            return $this->first(array_merge(config('api.response.user.basic'), ['id']))
                        ->setHidden(['is_followed', 'is_self']);
        };
    }
}