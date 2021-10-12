<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Rules\{NotCurrentPassword, ValidVerificationCode};
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        $passwordRule = Password::min(8)->mixedCase()->numbers()->symbols();
        $latestYear = now()->subYear(1)->year;
        $centuryAgo = $latestYear - 100;
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        return [
            'name' => Rule::when(
                in_array($routeName, ['auth.register', 'profile.update']),
                ['required', 'string', 'min:2']
            ),
            'email' => Rule::when(
                in_array($routeName, ['auth.register', 'settings.request-update.email']),
                ['required', 'string', 'email', Rule::unique('users')]
            ),
            'username' => Rule::when(
                in_array($routeName, ['auth.register', 'settings.request-update.username']),
                [
                    'required',
                    'string',
                    'between:' . config('validation.min_lengths.username') . ',' . config('validation.max_lengths.username'),
                    'regex:' . config('validation.formats.username'),
                    Rule::unique('users'),
                ]
            ),
            'phone_number' => Rule::when(
                in_array($routeName, ['auth.register', 'settings.request-update.phone-number']),
                [
                    'required',
                    'numeric',
                    'regex:' . config('validation.formats.phone_number'),
                    Rule::unique('users'),
                ]
            ),
            'gender' => Rule::when(
                $routeName === 'auth.register',
                ['required', Rule::in(['Male', 'Female'])]
            ),
            'birth_month' => Rule::when($routeName === 'auth.register', ['required', Rule::in($months)]),
            'birth_day' => Rule::when(
                $routeName === 'auth.register',
                ['required', 'numeric', 'between:1,31']
            ),
            'birth_year' => Rule::when($routeName === 'auth.register', [
                'required',
                'numeric',
                "between:{$centuryAgo},{$latestYear}",
            ]),
            'location' => Rule::when($routeName === 'profile.update', ['nullable', 'string']),
            'image_url' => Rule::when($routeName === 'profile.update', ['nullable', 'string']),
            'bio' => Rule::when(
                $routeName === 'profile.update',
                ['nullable', 'string', 'max:' . config('validation.max_lengths.bio')]
            ),
            'image' => Rule::when($routeName === 'profile.upload.profile-photo', [
                'required',
                'image',
                Rule::dimensions()
                    ->minWidth(config('validation.image.min_res'))
                    ->minHeight(config('validation.image.min_res'))
                    ->maxWidth(config('validation.image.max_res'))
                    ->maxHeight(config('validation.image.max_res'))
            ]),
            'password' => [
                Rule::requiredIf(in_array($routeName, [
                    'auth.register',
                    'settings.request-update.username',
                    'settings.request-update.email',
                    'settings.request-update.phone-number',
                ])),
                Rule::when($routeName === 'auth.register', ['confirmed', $passwordRule]),
                Rule::when(
                    in_array($routeName, [
                        'settings.request-update.username',
                        'settings.request-update.email',
                        'settings.request-update.phone-number',
                    ]),
                    ['current_password']
                ),
            ],
            'prefers_sms' => Rule::when(
                $routeName === 'settings.request-update.username',
                ['required', 'boolean']
            ),
            'prefers_sms_verification' => Rule::when(
                $routeName === 'auth.register',
                ['required', 'boolean']
            ),
            'current_password' => Rule::when(
                $routeName === 'settings.update.password',
                ['required', 'current_password']
            ),
            'new_password' => Rule::when($routeName === 'settings.update.password', [
                'required',
                $passwordRule,
                'confirmed',
                new NotCurrentPassword,
            ]),
            'code' => Rule::when(
                in_array($routeName, [
                    'settings.update.username',
                    'settings.update.email',
                    'settings.update.phone-number',
                ]),
                ['required', new ValidVerificationCode($routeName)]
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
        $minRes = config('validation.image.min_res');
        $maxRes = config('validation.image.max_res');

        return [
            'required' => 'The :attribute is required.',
            'numeric' => 'The :attribute must be numeric.',
            'boolean' => 'Must be true or false only.',
            'regex' => 'Invalid :attribute.',
            'in' => 'Invalid :attribute.',
            'email' => 'Invalid :attribute.',
            'max' => 'The number of characters exceeds the maximum length.',
            'unique' => 'Someone has already taken that :attribute.',
            'image' => 'Please upload an image file.',
            'dimensions' => "Resolution must be from {$minRes}x{$minRes} to {$maxRes}x{$maxRes}.",
            'current_password' => 'Incorrect password.',
            'between' => 'The :attribute must be between :min and :max characters long.',
            'between.numeric' => 'The :attribute must be between :min to :max only.',
            'password.confirmed' => 'Password not confirmed.',
            'new_password.confirmed' => 'New password not confirmed.',
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
            'prefers_sms_verification' => 'verification method',
            'prefers_sms' => 'verification method',
        ];
    }
}