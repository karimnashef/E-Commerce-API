<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\-\.\']+$/',
                'unique:users,name,' . $this->user()->id
            ],
            'phone' => [
                'sometimes',
                'regex:/^([0-9\s\-\+\(\)]){10,}$/'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number format is invalid.',
            'name.regex' => 'The name can only contain letters, spaces, hyphens, dots, and apostrophes.',
            'name.unique' => 'The name has already been taken.',
        ];
    }
}
