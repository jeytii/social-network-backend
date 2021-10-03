<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Rules\NotCurrentPassword;

class UpdateSettingRequest extends FormRequest
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
                Rule::requiredIf($routeName === 'settings.request-update.username'),
                'string',
                'between:' . config('api.min_lengths.username') . ',' . config('api.max_lengths.username'),
                'regex:' . config('api.formats.username'),
                Rule::unique('users'),
            ],
            'email' => [
                Rule::requiredIf($routeName === 'settings.request-update.email'),
                'string',
                'email',
                Rule::unique('users'),
            ],
            'phone_number' => [
                Rule::requiredIf($routeName === 'settings.request-update.phone-number'),
                'numeric',
                'regex:' . config('api.formats.phone_number'),
                Rule::unique('users'),
            ],
            'prefers_sms' => [
                Rule::requiredIf($routeName === 'settings.request-update.username'),
                'boolean',
            ],
            'password' => [
                Rule::requiredIf(in_array($routeName, [
                    'settings.request-update.username',
                    'settings.request-update.email',
                    'settings.request-update.phone-number',
                ])),
                'current_password',
            ],
            'current_password' => [
                Rule::requiredIf($routeName === 'settings.update.password'),
                'current_password',
            ],
            'new_password' => [
                Rule::requiredIf($routeName === 'settings.update.password'),
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'confirmed',
                new NotCurrentPassword,
            ]
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
            'numeric' => 'The :attribute must be numeric.',
            'boolean' => 'Must be true or false only.',
            'regex' => 'Please enter a valid :attribute.',
            'email' => 'Please enter a valid email address.',
            'unique' => 'Someone has already taken that :attribute.',
            'current_password' => 'Incorrect password.',
            'username.between' => 'The username must be between :min to :max characters long.',
            'email.required' => 'The email address field is required.',
            'email.unique' => 'Someone has already taken that email address.',
            'prefers_sms.required' => 'Please choose a verification type.',
            'current_password.required' => 'Please enter your current password.',
            'new_password.required' => 'Please enter your new password.',
            'new_password.confirmed' => 'New password must match with the confirmation.',
        ];
    }
}
