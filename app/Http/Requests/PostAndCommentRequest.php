<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Route;

class PostAndCommentRequest extends FormRequest
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
            'pid' => [
                Rule::requiredIf(Route::currentRouteName() === 'comments.store'),
                Rule::exists('posts', 'slug'),
            ],
            'body' => [
                'required',
                'string',
                'max:' . config('api.max_lengths.long_text')
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
            'pid.required' => 'Please choose a post to comment on.',
            'pid.exists' => 'Post does not exist.',
            'body.required' => 'Should not be blank.',
            'body.max' => 'The number of characters exceeds the maximum length.',
        ];
    }
}
