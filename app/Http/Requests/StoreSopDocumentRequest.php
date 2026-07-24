<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSopDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sop.documents.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:sop_templates,id'],
            'document_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('sop_documents', 'document_number')->where('version', 1),
            ],
            'title' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'integer', 'exists:users,id'],
            'effective_date' => ['nullable', 'date'],
            'review_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'variables' => ['array'],
            'variables.*' => ['nullable'],
        ];
    }
}
