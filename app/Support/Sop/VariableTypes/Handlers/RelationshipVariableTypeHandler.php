<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\Department;
use App\Models\SopDocument;
use App\Models\SopRole;
use App\Models\SopTemplateVariable;
use App\Models\User;
use App\Models\VariableDataType;
use App\Services\Sop\SopReferenceService;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;

class RelationshipVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::USER,
            VariableDataType::EMPLOYEE,
            VariableDataType::DEPARTMENT,
            VariableDataType::DESIGNATION,
            VariableDataType::SOP_REFERENCE,
            VariableDataType::SOP_DOCUMENT,
        ];
    }

    public function makeField(SopTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $field = Select::make($context->fieldName)
            ->options(fn (): array => $this->relationshipOptions($variable, $context))
            ->searchable()
            ->preload();

        return $this->applyCommonConfiguration($field, $variable)
            ->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        if ($defaultValue === null || $defaultValue === '') {
            return null;
        }

        return is_numeric($defaultValue) ? (int) $defaultValue : $defaultValue;
    }

    public function validationRules(SopTemplateVariable $variable): array
    {
        $baseRules = match ($variable->variableDataType?->code) {
            VariableDataType::DEPARTMENT => ['nullable', 'integer', 'exists:departments,id'],
            VariableDataType::USER, VariableDataType::EMPLOYEE => ['nullable', 'integer', 'exists:users,id'],
            VariableDataType::DESIGNATION => ['nullable', 'integer', 'exists:sop_roles,id'],
            VariableDataType::SOP_REFERENCE, VariableDataType::SOP_DOCUMENT => ['nullable', 'integer', 'exists:sop_documents,id'],
            default => ['nullable'],
        };

        return $this->mergeValidationRules($variable, $baseRules);
    }

    public function formatForStorage(SopTemplateVariable $variable, mixed $value): string
    {
        return $this->stringifyScalar($value);
    }

    public function formatForSubstitution(SopTemplateVariable $variable, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match ($variable->variableDataType?->code) {
            VariableDataType::USER, VariableDataType::EMPLOYEE => User::query()->whereKey($value)->value('name') ?? (string) $value,
            VariableDataType::DEPARTMENT => Department::query()->whereKey($value)->value('name') ?? (string) $value,
            VariableDataType::DESIGNATION => SopRole::query()->whereKey($value)->value('name') ?? (string) $value,
            VariableDataType::SOP_REFERENCE, VariableDataType::SOP_DOCUMENT => SopDocument::query()->whereKey($value)->value('document_number') ?? (string) $value,
            default => $this->formatForStorage($variable, $value),
        };
    }

    /**
     * @return array<int|string, string>
     */
    private function relationshipOptions(SopTemplateVariable $variable, VariableTypeFieldContext $context): array
    {
        return match ($variable->variableDataType?->code) {
            VariableDataType::USER, VariableDataType::EMPLOYEE => User::query()->orderBy('name')->pluck('name', 'id')->all(),
            VariableDataType::DEPARTMENT => Department::query()->orderBy('name')->pluck('name', 'id')->all(),
            VariableDataType::DESIGNATION => SopRole::query()->orderBy('sort_order')->pluck('name', 'id')->all(),
            VariableDataType::SOP_REFERENCE, VariableDataType::SOP_DOCUMENT => app(SopReferenceService::class)
                ->effectiveSopOptions($context->templateId),
            default => [],
        };
    }
}
