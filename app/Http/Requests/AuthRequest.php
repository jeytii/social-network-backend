<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
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
        $routeName = Route::currentRouteName();

        return [
            'username' => [
                Rule::when(
                    in_array($routeName, ['auth.login', 'auth.verify', 'auth.verify.resend']),
                    ['required']
                ),
                Rule::when(
                    $routeName === 'auth.verify.resend',
                    [new ExistsInEmailOrUsername]
                ),
            ],
            'email' => [
                Rule::when(
                    in_array($routeName, ['auth.forgot-password', 'auth.reset-password']),
                    ['required', 'email']
                ),
                Rule::when($routeName === 'auth.forgot-password', [Rule::exists('users')]),
                Rule::when(
                    $routeName === 'auth.reset-password',
                    [new PasswordResetEmailAddress($this->input('token'))]
                ),
                Rule::when(
                    in_array($routeName, ['auth.forgot-password', 'auth.reset-password']),
                    [new VerifiedEmailAddress]
                ),
            ],
            'password' => [
                Rule::when(
                    in_array($routeName, ['auth.login', 'auth.reset-password']),
                    ['required']
                ),
                Rule::when(
                    $routeName === 'auth.reset-password',
                    [Password::min(8)->mixedCase()->numbers()->symbols()]
                ),
            ],
            'password_confirmation' => Rule::when(
                $routeName === 'auth.reset-password',
                ['required', 'same:password']
            ),
            'code' => Rule::when(
                $routeName === 'auth.verify',
                ['required', Rule::exists('verifications')]
            ),
            'token' => Rule::when(
                $routeName === 'auth.reset-password',
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
