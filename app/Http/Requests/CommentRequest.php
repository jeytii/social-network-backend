<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CurrentValue;

class CommentRequest extends FormRequest
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

        if ($this->routeIs('comments.update')) {
            return [
                'body' => array_merge($body, [
                    new CurrentValue($this->route('comment'))
                ])
            ];
        }

        return [
            'post' => ['required', 'exists:posts,slug'],
            'body' => $body,
        ];
    }
}
