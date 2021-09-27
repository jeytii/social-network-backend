<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegistrationRequest extends FormRequest
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
        $latestYear = now()->subYear(1)->year;
        $centuryAgo = $latestYear - 100;

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        return [
            'name' => [
                'required',
                'string',
                'min:2',
            ],
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users'),
            ],
            'username' => [
                'required',
                'string',
                'between:6,30',
                'regex:' . config('api.formats.username'),
                Rule::unique('users'),
            ],
            'phone_number' => [
                'required',
                'numeric',
                'regex:' . config('api.formats.phone_number'),
            ],
            'gender' => [
                'required',
                'string',
                Rule::in(['Male', 'Female']),
            ],
            'birth_month' => [
                'required',
                'string',
                Rule::in($months),
            ],
            'birth_day' => [
                'required',
                'numeric',
                'between:1,31',
            ],
            'birth_year' => [
                'required',
                'numeric',
                "between:{$centuryAgo},{$latestYear}",
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'prefers_sms_verification' => [
                'required',
                'boolean',
            ],
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
            'unique' => 'You entered an unavailable :attribute.',
            'min' => 'The :attribute must be at least :min characters long.',
            'email' => 'Please enter a valid email address.',
            'regex' => 'Please enter a valid :attribute.',
            'in' => 'Please enter a valid :attribute.',
            'numeric' => 'The :attribute must be numeric.',
            'boolean' => 'Must be true or false only.',
            'username.between' => 'The username must be between :min to :max characters long.',
            'between.numeric' => 'The :attribute must be between :min to :max only.',
            'password.confirmed' => 'Please confirm your password.',
            'prefers_sms_verification.required' => 'Please choose the type of verification.',
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
