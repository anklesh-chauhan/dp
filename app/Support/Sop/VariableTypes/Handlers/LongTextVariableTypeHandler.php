<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\SopTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;

class LongTextVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::LONG_TEXT,
            VariableDataType::TEXTAREA,
        ];
    }

    public function makeField(SopTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        return $this->applyCommonConfiguration(
            Textarea::make($context->fieldName)->rows(4)->columnSpanFull(),
            $variable,
        )->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        return $defaultValue;
    }

    public function validationRules(SopTemplateVariable $variable): array
    {
        return $this->mergeValidationRules($variable, ['nullable', 'string']);
    }

    public function formatForStorage(SopTemplateVariable $variable, mixed $value): string
    {
        return $this->stringifyScalar($value);
    }

    public function formatForSubstitution(SopTemplateVariable $variable, mixed $value): string
    {
        return nl2br(e($this->formatForStorage($variable, $value)));
    }
}
