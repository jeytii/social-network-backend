<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Rules\{ExistsInEmailOrUsername, VerifiedEmailAddress};

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
        if ($this->routeIs('auth.login')) {
            return [
                'username' => ['required'],
                'password' => ['required'],
            ];
        }

        if ($this->routeIs('auth.verify')) {
            return [
                'token' => ['required', 'string'],
                'code' => ['required']
            ];
        }

        if ($this->routeIs('auth.verify.resend')) {
            return [
                'username' => ['required', new ExistsInEmailOrUsername],
            ];
        }

        if ($this->routeIs('auth.forgot-password')) {
            return [
                'email' => ['required', 'email', 'exists:users', new VerifiedEmailAddress],
            ];
        }

        if ($this->routeIs('auth.reset-password')) {
            return [
                'password' => ['required', Password::defaults()],
                'password_confirmation' => ['required', 'same:password'],
                'token' => ['required', 'string'],
            ];
        }

        return [];
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
