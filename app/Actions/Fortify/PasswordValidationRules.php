<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array
     */
    protected function passwordRules()
    {
        return [
            'required',
            'string',
            (new Password)
                ->requireUppercase()
                ->requireNumeric()
                ->requireSpecialCharacter()
                ->withMessage('The password must be at least 8 characters long and have one uppercase letter, number, and special character.'),
            'confirmed'
        ];
    }
}
