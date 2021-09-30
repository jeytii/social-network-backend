<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class CreateOrUpdateCommentRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:' . config('api.max_lengths.long_text')],
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
            'body.required' => 'Comment should not be blank.',
            'exists' => 'Post does not exist.',
            'max' => 'Maximum character length is :max.',
        ];
    }
}
