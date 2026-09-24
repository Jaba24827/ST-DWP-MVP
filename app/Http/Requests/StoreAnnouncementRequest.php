<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can_('announcements.publish') ?? false;
    }

    public function rules(): array
    {
        return [
            'title'    => ['required','string','max:160'],
            'body'     => ['required','string','max:4000'],
            'audience' => ['required','string','max:60'],
            'expires_at' => ['nullable','date','after:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Strip tags at the boundary; Blade escapes again at render time.
        $this->merge([
            'title' => strip_tags((string) $this->input('title')),
            'body'  => strip_tags((string) $this->input('body')),
        ]);
    }
}
