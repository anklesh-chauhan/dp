<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;

class BooleanVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::BOOLEAN,
            VariableDataType::CHECKBOX,
        ];
    }

    public function makeField(DocumentTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $field = match ($variable->variableDataType?->code) {
            VariableDataType::CHECKBOX => Checkbox::make($context->fieldName),
            default => Toggle::make($context->fieldName),
        };

        return $this->applyCommonConfiguration($field, $variable)
            ->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        return $this->parseBooleanDefault($defaultValue);
    }

    public function validationRules(DocumentTemplateVariable $variable): array
    {
        return $this->mergeValidationRules($variable, ['nullable', 'boolean']);
    }

    public function formatForStorage(DocumentTemplateVariable $variable, mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    public function formatForSubstitution(DocumentTemplateVariable $variable, mixed $value): string
    {
        if (is_bool($value)) {
            return $this->formatBooleanForSubstitution($value);
        }

        if (in_array($value, ['1', '0'], true)) {
            return $this->formatBooleanForSubstitution($value === '1');
        }

        return $this->formatBooleanForSubstitution($value);
    }
}
