<?php

namespace App\Http\Requests;

use App\Rules\ValidVerificationCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

class VerifyUserRequest extends FormRequest
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

        if ($routeName === 'settings.update.username') {
            $table = 'username_updates';
        }

        if ($routeName === 'settings.update.email') {
            $table = 'email_address_updates';
        }

        return [
            'code' => [
                'required',
                new ValidVerificationCode($table),
            ]
        ];
    }
}
