<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UploadProfilePhotoRequest extends FormRequest
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
        return [
            'image' => [
                'required',
                'image',
                Rule::dimensions()
                    ->minWidth(config('api.image.min_res'))
                    ->minHeight(config('api.image.min_res'))
                    ->maxWidth(config('api.image.max_res'))
                    ->maxHeight(config('api.image.max_res'))
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
        $minRes = config('api.image.min_res');
        $maxRes = config('api.image.max_res');

        return [
            'image' => 'Please upload an image file.',
            'dimensions' => "Resolution must be from {$minRes}x{$minRes} to {$maxRes}x{$maxRes}."
        ];
    }
}
