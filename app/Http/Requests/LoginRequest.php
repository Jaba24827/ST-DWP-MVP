<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * Validation is the input boundary. Everything that reaches a controller
     * has already been typed, length-bounded and normalised here.
     */
    public function rules(): array
    {
        return [
            'email'    => ['required','string','email:rfc','max:190'],
            'password' => ['required','string','max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function throttleKey(): string
    {
        return mb_strtolower($this->input('email')).'|'.$this->ip();
    }
}
