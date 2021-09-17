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
            return $this->with('user:id,slug,name,username,gender,image_url');
        };
    }
}