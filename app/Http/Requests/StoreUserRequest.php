<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can_('users.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required','string','max:120'],
            'email'       => ['required','email:rfc,dns','max:190','unique:users,email'],
            'password'    => ['required','string','confirmed', new StrongPassword],
            'role_id'     => ['required','integer','exists:roles,id'],
            'sector_id'   => ['nullable','integer','exists:sectors,id'],
            'site_id'     => ['nullable','integer','exists:sites,id'],
            'position'    => ['nullable','string','max:90'],
            'phone'       => ['nullable','string','regex:/^\+?[0-9 \-]{8,20}$/'],
            'mfa_channel' => ['required','in:email,sms'],
        ];
    }
}
