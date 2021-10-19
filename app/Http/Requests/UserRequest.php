<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Rules\NotCurrentPassword;
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
        $passwordRule = Password::min(8)->mixedCase()->numbers()->symbols();
        $latestYear = now()->subYear(1)->year;
        $centuryAgo = $latestYear - 100;
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        return [
            'name' => Rule::when(
                $this->routeIs(['auth.register', 'profile.update']),
                ['required', 'string', 'min:' . config('validation.min_lengths.name')]
            ),
            'email' => Rule::when(
                $this->routeIs(['auth.register', 'settings.change.email']),
                ['required', 'email', Rule::unique('users')]
            ),
            'username' => Rule::when(
                $this->routeIs(['auth.register', 'settings.change.username']),
                [
                    'required',
                    'string',
                    'between:' . config('validation.min_lengths.username') . ',' . config('validation.max_lengths.username'),
                    'regex:' . config('validation.formats.username'),
                    Rule::unique('users'),
                ]
            ),
            'phone_number' => Rule::when(
                $this->routeIs(['auth.register', 'settings.change.phone-number']),
                [
                    'required',
                    'regex:' . config('validation.formats.phone_number'),
                    Rule::unique('users'),
                ]
            ),
            'gender' => Rule::when(
                $this->routeIs('auth.register'),
                ['required', Rule::in(['Male', 'Female'])]
            ),
            'birth_month' => Rule::when(
                $this->routeIs('auth.register'),
                ['required', Rule::in($months)]
            ),
            'birth_day' => Rule::when(
                $this->routeIs('auth.register'),
                ['required', 'numeric', 'between:1,31']
            ),
            'birth_year' => Rule::when(
                $this->routeIs('auth.register'),
                [
                    'required',
                    'numeric',
                    "between:{$centuryAgo},{$latestYear}",
                ]
            ),
            'location' => Rule::when($this->routeIs('profile.update'), ['nullable', 'string']),
            'image_url' => Rule::when($this->routeIs('profile.update'), ['nullable', 'string']),
            'bio' => Rule::when(
                $this->routeIs('profile.update'),
                ['nullable', 'string', 'max:' . config('validation.max_lengths.bio')]
            ),
            'image' => Rule::when(
                $this->routeIs('profile.upload.profile-photo'),
                [
                    'required',
                    'image',
                    Rule::dimensions()
                        ->minWidth(config('validation.image.min_res'))
                        ->minHeight(config('validation.image.min_res'))
                        ->maxWidth(config('validation.image.max_res'))
                        ->maxHeight(config('validation.image.max_res'))
                ]
            ),
            'password' => [
                Rule::requiredIf($this->routeIs([
                    'auth.register',
                    'settings.change.username',
                    'settings.change.email',
                    'settings.change.phone-number',
                ])),
                Rule::when($this->routeIs('auth.register'), [$passwordRule]),
                Rule::when(
                    $this->routeIs([
                        'settings.change.username',
                        'settings.change.email',
                        'settings.change.phone-number',
                    ]),
                    ['current_password']
                ),
            ],
            'password_confirmation' => Rule::when(
                $this->routeIs('auth.register'),
                ['required', 'same:password']
            ),
            'current_password' => Rule::when(
                $this->routeIs('settings.change.password'),
                ['required', 'current_password']
            ),
            'new_password' => Rule::when(
                $this->routeIs('settings.change.password'),
                ['required', $passwordRule, new NotCurrentPassword]
            ),
            'new_password_confirmation' => Rule::when(
                $this->routeIs('settings.change.password'),
                ['required', 'same:new_password']
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
            'required' => ':Attribute is required.',
            'numeric' => ':Attribute must be numeric.',
            'regex' => 'Invalid :attribute.',
            'in' => 'Invalid :attribute.',
            'email' => 'Invalid :attribute.',
            'max' => 'The number of characters exceeds the maximum length.',
            'unique' => ':Attribute already taken.',
            'image' => 'Please upload an image file.',
            'dimensions' => "Resolution must be from {$minRes}x{$minRes} to {$maxRes}x{$maxRes}.",
            'current_password' => 'Incorrect password.',
            'between' => ':Attribute must be between :min and :max characters long.',
            'between.numeric' => ':Attribute must be between :min and :max.',
            'same' => 'Does not match with the password above.',
            'password_confirmation.required' => 'Confirmation is required.',
            'new_password_confirmation.required' => 'Confirmation is required.',
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
        ];
    }
}