<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use App\Support\Sop\VariableTypes\VariableTypeRegistry;
use Filament\Forms\Components\Field;

class TemplateVariableFieldBuilder
{
    /**
     * Variables populated automatically during document generation.
     *
     * @var list<string>
     */
    private const AUTO_POPULATED_NAMES = [
        'document_number',
    ];

    /**
     * @param  list<string>  $additionalExcludedNames
     * @return array<int, Field>
     */
    public static function fields(?int $templateVersionId, ?int $templateId = null, array $additionalExcludedNames = []): array
    {
        if ($templateVersionId === null || $templateVersionId === 0) {
            return [];
        }

        $registry = app(VariableTypeRegistry::class);

        return DocumentTemplateVersion::query()
            ->with(['variables.variableDataType'])
            ->find($templateVersionId)
            ?->variables
            ->reject(fn (DocumentTemplateVariable $variable): bool => self::shouldExcludeFromForm($variable, $additionalExcludedNames))
            ->map(fn (DocumentTemplateVariable $variable): Field => $registry->makeField(
                $variable,
                VariableTypeFieldContext::forDocumentCreation($variable, $templateId),
            ))
            ->values()
            ->all() ?? [];
    }

    /**
     * @param  list<string>  $additionalExcludedNames
     * @return array<string, mixed>
     */
    public static function defaultValues(?int $templateVersionId, array $additionalExcludedNames = []): array
    {
        if ($templateVersionId === null) {
            return [];
        }

        $registry = app(VariableTypeRegistry::class);

        return DocumentTemplateVersion::query()
            ->with(['variables.variableDataType'])
            ->find($templateVersionId)
            ?->variables
            ->reject(fn (DocumentTemplateVariable $variable): bool => self::shouldExcludeFromForm($variable, $additionalExcludedNames))
            ->mapWithKeys(fn (DocumentTemplateVariable $variable): array => [
                $variable->name => $registry->parseDefaultValue($variable),
            ])
            ->all() ?? [];
    }

    /**
     * @param  list<string>  $additionalExcludedNames
     */
    public static function shouldExcludeFromForm(DocumentTemplateVariable $variable, array $additionalExcludedNames = []): bool
    {
        if (in_array($variable->name, $additionalExcludedNames, true)) {
            return true;
        }

        if (in_array($variable->name, self::AUTO_POPULATED_NAMES, true)) {
            return true;
        }

        if ($variable->variableDataType?->hasCode(VariableDataType::DOCUMENT_NUMBER)) {
            return true;
        }

        return match ($variable->name) {
            'department' => ! $variable->variableDataType?->hasCode(VariableDataType::DEPARTMENT),
            'referenced_sop' => ! $variable->variableDataType?->hasCode(VariableDataType::SOP_REFERENCE)
                && ! $variable->variableDataType?->hasCode(VariableDataType::CONTROLLED_DOCUMENT),
            default => false,
        };
    }

    public static function editField(DocumentTemplateVariable $variable, ?int $templateId = null): Field
    {
        return app(VariableTypeRegistry::class)->makeField(
            $variable,
            VariableTypeFieldContext::forDocumentEdit($variable, $templateId),
        );
    }
}
