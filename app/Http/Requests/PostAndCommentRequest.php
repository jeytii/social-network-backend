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
            'post' => [
                Rule::requiredIf($this->routeIs('comments.store')),
                Rule::exists('posts', 'slug'),
            ],
            'body' => [
                'required',
                'string',
                'max:' . config('validation.max_lengths.long_text')
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
            'exists' => ':Attribute does not exist.',
            'post.required' => 'Please choose a post to comment on.',
            'body.required' => 'Should not be blank.',
            'body.max' => 'The number of characters exceeds the maximum length.',
        ];
    }
}
