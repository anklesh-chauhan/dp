<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\SopTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;

class ChoiceVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::SELECT,
            VariableDataType::MULTI_SELECT,
            VariableDataType::RADIO,
        ];
    }

    public function makeField(SopTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $options = $this->choiceOptions($variable);

        $field = match ($variable->variableDataType?->code) {
            VariableDataType::MULTI_SELECT => Select::make($context->fieldName)->multiple(),
            VariableDataType::RADIO => Radio::make($context->fieldName)->options($options),
            default => Select::make($context->fieldName)->searchable(),
        };

        if (! $field instanceof Radio) {
            $field = $field->options($options);
        }

        if ($field instanceof Select) {
            $field = $field->preload();
        }

        return $this->applyCommonConfiguration($field, $variable)
            ->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        if ($defaultValue === null || $defaultValue === '') {
            return null;
        }

        $decoded = json_decode($defaultValue, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $defaultValue;
    }

    public function validationRules(SopTemplateVariable $variable): array
    {
        $baseRules = match ($variable->variableDataType?->code) {
            VariableDataType::MULTI_SELECT => ['nullable', 'array'],
            default => ['nullable', 'string'],
        };

        return $this->mergeValidationRules($variable, $baseRules);
    }

    public function formatForStorage(SopTemplateVariable $variable, mixed $value): string
    {
        if ($variable->variableDataType?->hasCode(VariableDataType::MULTI_SELECT)) {
            return json_encode(array_values((array) $value)) ?: '[]';
        }

        return $this->stringifyScalar($value);
    }

    public function formatForSubstitution(SopTemplateVariable $variable, mixed $value): string
    {
        if ($variable->variableDataType?->hasCode(VariableDataType::MULTI_SELECT)) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $value = $decoded;
                }
            }

            return collect((array) $value)
                ->map(fn (mixed $item): string => $this->choiceOptions($variable)[(string) $item] ?? (string) $item)
                ->implode(', ');
        }

        $options = $this->choiceOptions($variable);
        $stringValue = $this->stringifyScalar($value);

        return $options[$stringValue] ?? $stringValue;
    }
}
