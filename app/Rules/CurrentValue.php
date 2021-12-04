<?php

namespace App\Rules;

use App\Models\{Post, Comment};
use Illuminate\Contracts\Validation\Rule;

class CurrentValue implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param Post|Comment  $model
     * @return void
     */
    public function __construct(Post|Comment $model)
    {
        $this->model = $model;
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
        return $this->model->body !== $value;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'No changes made.';
    }
}
