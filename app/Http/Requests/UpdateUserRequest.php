<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $currentYear = now()->subYear(1)->year;
        $centuryAgo = $currentYear - 100;

        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        return [
            'name' => ['required', 'string'],
            'birth_month' => [
                'string',
                Rule::in($months),
                Rule::requiredIf(request()->has('birth_day') || request()->has('birth_year')),
            ],
            'birth_day' => [
                'numeric',
                'between:1,31',
                Rule::requiredIf(request()->has('birth_month') || request()->has('birth_year')),
            ],
            'birth_year' => [
                'numeric',
                "between:{$centuryAgo},{$currentYear}",
                Rule::requiredIf(request()->has('birth_month') || request()->has('birth_day')),
            ],
            'location' => ['string'],
            'bio' => ['string', 'max:130'],
            'image_url' => ['string']
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
            'max' => 'The number of characters exceeds the maximum length.',
            'between' => 'The :attribute must be :min to :max characters long.',
            'birth_day.between' => 'Birth day must be between 1 and 31 only.',
        ];
    }
}
