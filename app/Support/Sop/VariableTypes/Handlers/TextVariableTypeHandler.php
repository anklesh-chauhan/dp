<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;

class TextVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::TEXT,
            VariableDataType::URL,
            VariableDataType::EMAIL,
            VariableDataType::PHONE,
        ];
    }

    public function makeField(DocumentTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $field = match ($variable->variableDataType?->code) {
            VariableDataType::URL => TextInput::make($context->fieldName)->url(),
            VariableDataType::EMAIL => TextInput::make($context->fieldName)->email(),
            VariableDataType::PHONE => TextInput::make($context->fieldName)->tel(),
            default => TextInput::make($context->fieldName),
        };

        return $this->applyCommonConfiguration($field, $variable)
            ->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        return $defaultValue;
    }

    public function validationRules(DocumentTemplateVariable $variable): array
    {
        $baseRules = match ($variable->variableDataType?->code) {
            VariableDataType::URL => ['nullable', 'string', 'url'],
            VariableDataType::EMAIL => ['nullable', 'string', 'email'],
            VariableDataType::PHONE => ['nullable', 'string'],
            default => ['nullable', 'string'],
        };

        return $this->mergeValidationRules($variable, $baseRules);
    }

    public function formatForStorage(DocumentTemplateVariable $variable, mixed $value): string
    {
        return $this->stringifyScalar($value);
    }

    public function formatForSubstitution(DocumentTemplateVariable $variable, mixed $value): string
    {
        return $this->formatForStorage($variable, $value);
    }
}
