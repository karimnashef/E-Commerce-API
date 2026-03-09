<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\-\.\']+$/'
            ],
            'phone' => [
                'required',
                'regex:/^([0-9\s\-\+\(\)]){10,}$/',
                'unique:users,phone'
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.uncompromised' => 'This password has been exposed in data breaches. Please choose a different password.',
            'phone.regex' => 'The phone number format is invalid.',
            'name.regex' => 'The name can only contain letters, spaces, hyphens, dots, and apostrophes.',
        ];
    }
}
