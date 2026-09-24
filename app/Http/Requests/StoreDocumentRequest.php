<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can_('documents.upload') ?? false;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required','string','max:160'],
            'sector_id'      => ['nullable','integer','exists:sectors,id'],
            'classification' => ['required','in:public,internal,restricted'],
            // Extension AND sniffed mime must both pass; size in kilobytes.
            'file'           => ['required','file','max:10240','mimes:pdf,doc,docx,xls,xlsx,png,jpg','mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/png,image/jpeg'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes'     => 'Upload a PDF, Word, Excel or image file.',
            'file.max'       => 'Files must be 10 MB or smaller.',
            'classification.in' => 'Choose public, internal or restricted.',
        ];
    }
}
