<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Filament\Support\ContentAiAssist;
use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Services\AI\Enums\ContentAssistFormat;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;

class LongTextVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::LONG_TEXT,
            VariableDataType::TEXTAREA,
        ];
    }

    public function makeField(DocumentTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $field = $this->applyCommonConfiguration(
            Textarea::make($context->fieldName)->rows(4)->columnSpanFull(),
            $variable,
        )->rules($this->validationRules($variable));

        return ContentAiAssist::attach(
            field: $field,
            format: ContentAssistFormat::Plain,
            context: fn (Get $get): array => [
                'field_label' => $variable->label,
                'subject' => trim((string) ($get('title') ?? '')),
                'extra' => 'Controlled document textarea variable: '.$variable->name,
            ],
        );
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
        return nl2br(e($this->formatForStorage($variable, $value)));
    }
}
