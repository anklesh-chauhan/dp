<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Filament\Support\ContentAiAssist;
use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Services\AI\Enums\ContentAssistFormat;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Schemas\Components\Utilities\Get;

class RichTextVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [VariableDataType::RICH_TEXT];
    }

    public function makeField(DocumentTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $field = $this->applyCommonConfiguration(
            RichEditor::make($context->fieldName)->columnSpanFull(),
            $variable,
        )->rules($this->validationRules($variable));

        return ContentAiAssist::attach(
            field: $field,
            format: ContentAssistFormat::Html,
            context: fn (Get $get): array => [
                'field_label' => $variable->label,
                'subject' => trim((string) ($get('title') ?? '')),
                'extra' => 'Controlled document rich-text variable: '.$variable->name,
            ],
        );
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        return $defaultValue;
    }

    public function validationRules(DocumentTemplateVariable $variable): array
    {
        return $this->mergeValidationRules($variable, ['nullable']);
    }

    public function formatForStorage(DocumentTemplateVariable $variable, mixed $value): string
    {
        if (is_array($value)) {
            return RichContentRenderer::make($value)->toHtml();
        }

        return $this->stringifyScalar($value);
    }

    public function formatForSubstitution(DocumentTemplateVariable $variable, mixed $value): string
    {
        return $this->formatForStorage($variable, $value);
    }
}
