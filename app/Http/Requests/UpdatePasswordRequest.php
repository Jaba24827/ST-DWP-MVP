<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can_('account.security') ?? false;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required','current_password'],
            'password'         => [
                'required','string','confirmed',
                new StrongPassword,
                // Laravel's own breach check, on top of the shape rule.
                Password::min(12)->uncompromised(),
                'different:current_password',
            ],
        ];
    }
}
