<?php

namespace App\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class ValidVerificationCode implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param string  $table
     * @return void
     */
    public function __construct(string $table)
    {
        $this->table = $table;
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
        return DB::table($this->table)
                ->where('user_id', auth()->id())
                ->where('code', $value)
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
        return 'Invalid verification code.';
    }
}
