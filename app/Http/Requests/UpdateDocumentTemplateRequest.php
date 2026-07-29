<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DocumentTemplate;
use App\Models\TemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('document_template');

        return $template instanceof DocumentTemplate
            && $template->templateStatus?->hasCode(TemplateStatus::DRAFT)
            && ($this->user()?->can('sop.templates.update') ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var DocumentTemplate|null $template */
        $template = $this->route('document_template');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('document_templates', 'code')->ignore($template?->id)],
            'description' => ['nullable', 'string'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'category_id' => ['required', 'integer', 'exists:document_categories,id'],
            'document_type_id' => ['required', 'integer', Rule::exists('document_types', 'id')],
            'template_status_id' => ['required', 'integer', Rule::exists('template_statuses', 'id')],
            'current_version' => ['required', 'integer', 'min:0', 'gte:'.$template?->current_version],
        ];
    }
}
