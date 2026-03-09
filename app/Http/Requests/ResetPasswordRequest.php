<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'exists:users,name'],
            'key' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.exists' => 'This user does not exist.',
            'key.required' => 'The key field is required.',
        ];
    }
}
