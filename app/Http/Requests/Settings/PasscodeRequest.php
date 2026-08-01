<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class PasscodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'passcode' => ['required', 'digits_between:4,6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'passcode.digits_between' => 'The passcode must be between 4 and 6 digits.',
            'passcode.confirmed' => 'The passcode confirmation does not match.',
        ];
    }
}
