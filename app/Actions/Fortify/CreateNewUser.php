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
            'email_address' => [
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
            'unique' => 'Oops! :attribute already exists.',
            'username.regex' => 'Only letters, numbers, dots, and underscores are allowed.',
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
        Validator::make($input, $this->rules(), $this->messages())->validate();

        return User::create([
            'name' => $input['name'],
            'email_address' => $input['email_address'],
            'username' => $input['username'],
            'gender' => $input['gender'],
            'password' => $input['password'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
