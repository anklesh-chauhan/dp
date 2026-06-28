<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TemplateStatus;
use App\Models\SopTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateSopTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('sop_template');

        return $template instanceof SopTemplate
            && $template->status === TemplateStatus::Draft
            && ($this->user()?->can('sop.templates.update') ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var SopTemplate|null $template */
        $template = $this->route('sop_template');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('sop_templates', 'code')->ignore($template?->id)],
            'description' => ['nullable', 'string'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'category_id' => ['required', 'integer', 'exists:document_categories,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'status' => ['required', new Enum(TemplateStatus::class)],
            'current_version' => ['required', 'integer', 'min:0', 'gte:'.$template?->current_version],
        ];
    }
}
