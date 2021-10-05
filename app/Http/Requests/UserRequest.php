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
        $latestYear = now()->subYear(1)->year;
        $centuryAgo = $latestYear - 100;

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        return [
            'name' => Rule::when(
                in_array($routeName, ['auth.register', 'profile.update']),
                [
                    'required',
                    'string',
                    'min:2',
                ]
            ),
            'email' => Rule::when(
                in_array($routeName, ['auth.register', 'settings.request-update.email']),
                [
                    'required',
                    'string',
                    'email',
                    Rule::unique('users'),
                ]
            ),
            'username' => Rule::when(
                in_array($routeName, ['auth.register', 'settings.request-update.username']),
                [
                    'required',
                    'string',
                    'between:' . config('api.min_lengths.username') . ',' . config('api.max_lengths.username'),
                    'regex:' . config('api.formats.username'),
                    Rule::unique('users'),
                ]
            ),
            'phone_number' => Rule::when(
                in_array($routeName, ['auth.register', 'settings.request-update.phone-number']),
                [
                    'required',
                    'numeric',
                    'regex:' . config('api.formats.phone_number'),
                    Rule::unique('users'),
                ]
            ),
            'gender' => Rule::when($routeName === 'auth.register', [
                'required',
                'string',
                Rule::in(['Male', 'Female']),
            ]),
            'birth_month' => Rule::when($routeName === 'auth.register', [
                'required',
                'string',
                Rule::in($months),
            ]),
            'birth_day' => Rule::when($routeName === 'auth.register', [
                'required',
                'numeric',
                'between:1,31',
            ]),
            'birth_year' => Rule::when($routeName === 'auth.register', [
                'required',
                'numeric',
                "between:{$centuryAgo},{$latestYear}",
            ]),
            'location' => Rule::when($routeName === 'profile.update', ['nullable', 'string']),
            'image_url' => Rule::when($routeName === 'profile.update', ['nullable', 'string']),
            'bio' => Rule::when($routeName === 'profile.update', [
                'nullable',
                'string',
                'max:' . config('api.max_lengths.bio')
            ]),
            'image' => Rule::when($routeName === 'profile.upload.profile-photo', [
                'required',
                'image',
                Rule::dimensions()
                    ->minWidth(config('api.image.min_res'))
                    ->minHeight(config('api.image.min_res'))
                    ->maxWidth(config('api.image.max_res'))
                    ->maxHeight(config('api.image.max_res'))
            ]),
            'password' => [
                Rule::requiredIf(in_array($routeName, [
                    'auth.register',
                    'settings.request-update.username',
                    'settings.request-update.email',
                    'settings.request-update.phone-number',
                ])),
                Rule::when($routeName === 'auth.register', [
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols(),
                ]),
                Rule::when(
                    in_array($routeName, [
                        'settings.request-update.username',
                        'settings.request-update.email',
                        'settings.request-update.phone-number',
                    ]),
                    'current_password'
                ),
            ],
            'prefers_sms' => Rule::when($routeName === 'settings.request-update.username', [
                'required',
                'boolean',
            ]),
            'prefers_sms_verification' => Rule::when($routeName === 'auth.register', [
                'required',
                'boolean',
            ]),
            'current_password' => Rule::when($routeName === 'settings.update.password', [
                'required',
                'current_password',
            ]),
            'new_password' => Rule::when($routeName === 'settings.update.password', [
                'required',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'confirmed',
                new NotCurrentPassword,
            ]),
            'code' => Rule::when(
                in_array($routeName, [
                    'settings.update.username',
                    'settings.update.email',
                    'settings.update.phone-number',
                ]),
                [
                    'required',
                    new ValidVerificationCode($routeName),
                ]
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
        $minRes = config('api.image.min_res');
        $maxRes = config('api.image.max_res');

        return [
            'numeric' => 'The :attribute must be numeric.',
            'boolean' => 'Must be true or false only.',
            'regex' => 'Please enter a valid :attribute.',
            'in' => 'Please enter a valid :attribute.',
            'max' => 'The number of characters exceeds the maximum length.',
            'email' => 'Please enter a valid email address.',
            'unique' => 'Someone has already taken that :attribute.',
            'image' => 'Please upload an image file.',
            'dimensions' => "Resolution must be from {$minRes}x{$minRes} to {$maxRes}x{$maxRes}.",
            'current_password' => 'Incorrect password.',
            'between.numeric' => 'The :attribute must be between :min to :max only.',
            'username.between' => 'The username must be between :min to :max characters long.',
            'email.required' => 'The email address field is required.',
            'email.unique' => 'Someone has already taken that email address.',
            'password.confirmed' => 'Please confirm your password.',
            'current_password.required' => 'Please enter your current password.',
            'new_password.required' => 'Please enter your new password.',
            'new_password.confirmed' => 'New password must match with the confirmation.',
            'prefers_sms_verification.required' => 'Please choose the type of verification.',
            'prefers_sms.required' => 'Please choose a verification type.',
        ];
    }
}