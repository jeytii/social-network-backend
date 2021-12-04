<?php

namespace App\Http\Requests;

use App\Rules\CurrentValue;
use Illuminate\Foundation\Http\FormRequest;

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
        $body = [
            'required',
            'string',
            'max:' . config('validation.max_lengths.long_text'),
        ];

        if ($this->routeIs('posts.store')) {
            return compact('body');
        }

        if ($this->routeIs('posts.update')) {
            return [
                'body' => array_merge($body, [
                    new CurrentValue('posts', $this->query('post'))
                ])
            ];
        }

        if ($this->routeIs('comments.store')) {
            return [
                'post' => ['required', 'exists:posts,slug'],
                'body' => $body,
            ];
        }

        if ($this->routeIs('comments.update')) {
            return [
                'body' => array_merge($body, [
                    new CurrentValue('comments', $this->query('comment'))
                ])
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
        return [
            'exists' => ':Attribute does not exist.',
            'post.required' => 'Please choose a post to comment on.',
            'body.required' => 'Should not be blank.',
            'body.max' => 'The number of characters exceeds the maximum length.',
        ];
    }
}
