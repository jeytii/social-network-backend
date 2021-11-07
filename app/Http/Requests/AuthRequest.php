<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Rules\{
    ExistsInEmailOrUsername,
    PasswordResetEmailAddress,
    VerifiedEmailAddress
};

class AuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => [
                Rule::when(
                    $this->routeIs(['auth.login', 'auth.verify.resend']),
                    ['required']
                ),
                Rule::when(
                    $this->routeIs('auth.verify.resend'),
                    [new ExistsInEmailOrUsername]
                ),
            ],
            'email' => [
                Rule::when(
                    $this->routeIs(['auth.forgot-password', 'auth.reset-password']),
                    ['required', 'email']
                ),
                Rule::when($this->routeIs('auth.forgot-password'), [Rule::exists('users')]),
                Rule::when(
                    $this->routeIs('auth.reset-password'),
                    [new PasswordResetEmailAddress($this->input('token'))]
                ),
                Rule::when(
                    $this->routeIs(['auth.forgot-password', 'auth.reset-password']),
                    [new VerifiedEmailAddress]
                ),
            ],
            'password' => [
                Rule::when(
                    $this->routeIs(['auth.login', 'auth.reset-password']),
                    ['required']
                ),
                Rule::when(
                    $this->routeIs('auth.reset-password'),
                    [Password::min(config('validation.min_lengths.password'))->mixedCase()->numbers()->symbols()]
                ),
            ],
            'password_confirmation' => Rule::when(
                $this->routeIs('auth.reset-password'),
                ['required', 'same:password']
            ),
            'code' => Rule::when(
                $this->routeIs('auth.verify'),
                ['required', Rule::exists('verifications')]
            ),
            'token' => Rule::when(
                $this->routeIs('auth.reset-password'),
                ['required', 'string']
            )
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'required' => ':Attribute is required.',
            'min' => ':Attribute must be at least :min characters long.',
            'boolean' => 'Must be true or false only.',
            'email' => 'Invalid :attribute.',
            'exists' => ':Attribute does not exist.',
            'same' => 'Does not match with the password above.',
            'password_confirmation.required' => 'Confirmation is required.',
            'code.exists' => 'Invalid :attribute.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'email' => 'email address',
            'code' => 'verification code',
        ];
    }
}
