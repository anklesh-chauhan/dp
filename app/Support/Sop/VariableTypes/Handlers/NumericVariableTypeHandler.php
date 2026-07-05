<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\SopTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;

class NumericVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::INTEGER,
            VariableDataType::DECIMAL,
            VariableDataType::NUMBER,
            VariableDataType::CURRENCY,
            VariableDataType::PERCENTAGE,
        ];
    }

    public function makeField(SopTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $field = TextInput::make($context->fieldName)->numeric();

        $field = match ($variable->variableDataType?->code) {
            VariableDataType::INTEGER => $field->integer(),
            VariableDataType::CURRENCY => $field->prefix(config('app.currency_prefix', '₹')),
            VariableDataType::PERCENTAGE => $field->suffix('%'),
            default => $field,
        };

        return $this->applyCommonConfiguration($field, $variable)
            ->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        if ($defaultValue === null || $defaultValue === '') {
            return null;
        }

        return is_numeric($defaultValue) ? $defaultValue : null;
    }

    public function validationRules(SopTemplateVariable $variable): array
    {
        $baseRules = match ($variable->variableDataType?->code) {
            VariableDataType::INTEGER => ['nullable', 'integer'],
            default => ['nullable', 'numeric'],
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
            VariableDataType::CURRENCY => config('app.currency_prefix', '₹').number_format((float) $value, 2),
            VariableDataType::PERCENTAGE => rtrim(rtrim(number_format((float) $value, 2), '0'), '.').'%',
            VariableDataType::INTEGER => (string) (int) $value,
            default => $this->formatForStorage($variable, $value),
        };
    }
}
