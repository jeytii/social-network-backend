<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PasswordResetEmailAddress implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param string|null  $token
     * @return void
     */
    public function __construct(?string $token)
    {
        $this->token = $token;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return DB::table('password_resets')
                ->where('email', $value)
                ->where('token', $this->token)
                ->where('expiration', '>', now())
                ->whereNull('completed_at')
                ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid email address or token.';
    }
}
