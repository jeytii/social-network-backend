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
        $latestYear = now()->subYear(1)->year;
        $centuryAgo = $latestYear - 100;

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
                Rule::requiredIf(auth()->user()->no_birthdate),
                'string',
                Rule::in($months),
            ],
            'birth_day' => [
                Rule::requiredIf(auth()->user()->no_birthdate),
                'numeric',
                'between:1,31',
            ],
            'birth_year' => [
                Rule::requiredIf(auth()->user()->no_birthdate),
                'numeric',
                "between:{$centuryAgo},{$latestYear}",
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
