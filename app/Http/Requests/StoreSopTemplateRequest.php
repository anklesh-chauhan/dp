<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSopTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sop.templates.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:sop_templates,code'],
            'description' => ['nullable', 'string'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'category_id' => ['required', 'integer', 'exists:document_categories,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'status' => ['nullable', new Enum(TemplateStatus::class)],
        ];
    }
}
