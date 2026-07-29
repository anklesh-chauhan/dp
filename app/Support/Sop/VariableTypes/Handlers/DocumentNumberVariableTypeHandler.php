<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;

class DocumentNumberVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [VariableDataType::DOCUMENT_NUMBER];
    }

    public function makeField(DocumentTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        return $this->applyCommonConfiguration(
            TextInput::make($context->fieldName)->disabled()->dehydrated(),
            $variable,
        )->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        return $defaultValue;
    }

    public function validationRules(DocumentTemplateVariable $variable): array
    {
        return $this->mergeValidationRules($variable, ['nullable', 'string']);
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
