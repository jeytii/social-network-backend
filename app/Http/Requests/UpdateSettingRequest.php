<?php

namespace App\Http\Requests;

use App\Rules\IsCurrentPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

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
            'password' => ['required', new IsCurrentPassword]
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
            'username.between' => 'The username must be between :min to :max characters long.',
            'email.required' => 'The email address field is required.',
            'email.unique' => 'Someone has already taken that email address.',
            'prefers_sms.required' => 'Please choose a verification type.',
        ];
    }
}
