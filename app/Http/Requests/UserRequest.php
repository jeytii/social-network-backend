<?php

namespace App\Http\Requests;

use App\Rules\{ValidDate, NotCurrentPassword};
use Illuminate\Validation\Rule;
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
        $minDate = now()->subCentury()->format('Y-m-d');
        $maxDate = now()->subYears(18)->format('Y-m-d');
        $minNameLength = config('validation.min_lengths.name');
        $minUsernameLength = config('validation.min_lengths.username');
        $maxUsernameLength = config('validation.max_lengths.username');
        $maxBioLength = config('validation.max_lengths.bio');
        $usernameFormat = config('validation.formats.username');
        $usernameRule = [
            'required',
            'string',
            'between:' . $minUsernameLength . ',' . $maxUsernameLength,
            'regex:' . $usernameFormat,
            'unique:users',
        ];
        $birthDateRule = [
            'required',
            'date',
            new ValidDate,
            'after_or_equal:' . $minDate,
            'before_or_equal:' . $maxDate,
        ];

        if ($this->routeIs('auth.register')) {
            return [
                'name' => ['required', 'string', 'min:' . $minNameLength],
                'email' => ['required', 'email', 'unique:users'],
                'username' => $usernameRule,
                'gender' => ['required', 'in:Male,Female'],
                'birth_date' => $birthDateRule,
                'password' => ['required', Password::defaults()],
                'password_confirmation' => ['required', 'same:password'],
            ];
        }

        if ($this->routeIs('profile.update')) {
            return [
                'name' => ['required', 'string', 'min:' . $minNameLength],
                'birth_date' => $birthDateRule,
                'bio' => ['nullable', 'string', 'max:' . $maxBioLength],
                'image_url' => ['nullable', 'string'],
            ];
        }

        if ($this->routeIs('settings.change.email')) {
            return [
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'current_password'],
            ];
        }

        if ($this->routeIs('settings.change.username')) {
            return [
                'username' => $usernameRule,
                'password' => ['required', 'current_password'],
            ];
        }

        if ($this->routeIs('settings.change.password')) {
            return [
                'current_password' => ['required', 'current_password'],
                'new_password' => ['required', Password::defaults(), new NotCurrentPassword],
                'new_password_confirmation' => ['required', 'same:new_password'],
            ];
        }

        if ($this->routeIs('profile.upload.profile-photo')) {
            return [
                'image' => [
                    'required',
                    'image',
                    Rule::dimensions()
                        ->minWidth(config('validation.image.min_res'))
                        ->minHeight(config('validation.image.min_res'))
                        ->maxWidth(config('validation.image.max_res'))
                        ->maxHeight(config('validation.image.max_res')),
                ]
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
        $minRes = config('validation.image.min_res');
        $maxRes = config('validation.image.max_res');

        return [
            'required' => ':Attribute is required.',
            'regex' => 'Invalid :attribute.',
            'in' => 'Invalid :attribute.',
            'email' => 'Invalid :attribute.',
            'max' => 'The number of characters exceeds the maximum length.',
            'unique' => ':Attribute already taken.',
            'image' => 'Please upload an image file.',
            'dimensions' => "Resolution must be from {$minRes}x{$minRes} to {$maxRes}x{$maxRes}.",
            'current_password' => 'Incorrect password.',
            'between' => ':Attribute must be between :min and :max characters long.',
            'same' => 'Does not match with the password above.',
            'date' => 'Invalid :attribute.',
            'before_or_equal' => 'You must be 18 to 100 years old.',
            'after_or_equal' => 'You must be 18 to 100 years old.',
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
