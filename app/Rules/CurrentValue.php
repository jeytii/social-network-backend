<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CurrentValue implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param string  $relationship
     * @param string  $slug
     * @return void
     */
    public function __construct(string $relationship, string $slug)
    {
        $this->relationship = $relationship;
        $this->slug = $slug;
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
        $data = auth()->user()->{$this->relationship}()->firstWhere('slug', $this->slug);

        return $data->body === $value;
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
