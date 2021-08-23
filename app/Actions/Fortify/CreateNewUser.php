<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\{Hash, Validator};

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Generate validation rules.
     *
     * @return array
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2'],
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique(User::class),
            ],
            'username' => [
                'required',
                'string',
                'between:6,20',
                'regex:/^[a-zA-Z0-9._]+$/',
                Rule::unique(User::class)
            ],
            'gender' => ['required', 'in:Male,Female'],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * Custom validation error messages.
     *
     * @return array
     */
    private function messages(): array
    {
        return [
            'min' => 'The :attribute must be at least :min characters long.',
            'between' => 'The :attribute must be :min to :max characters long.',
            'unique' => 'Someone has already taken that :attribute.',
            'email' => 'Please enter a valid :attribute.',
            'username.regex' => 'Only letters, numbers, dots, and underscores are allowed.',
        ];
    }

    /**
     * Custom attribute names.
     *
     * @return array
     */
    private function attributes(): array
    {
        return [
            'email' => 'email address'
        ];
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array  $input
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        Validator::make(
            $input,
            $this->rules(),
            $this->messages(),
            $this->attributes(),
        )->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'username' => $input['username'],
            'gender' => $input['gender'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
