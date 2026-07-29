<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes;

use App\Models\DocumentTemplateVariable;

final class VariableTypeFieldContext
{
    public function __construct(
        public readonly string $fieldName,
        public readonly ?int $templateId = null,
    ) {}

    public static function forDocumentCreation(DocumentTemplateVariable $variable, ?int $templateId): self
    {
        return new self("variables.{$variable->name}", $templateId);
    }

    public static function forDocumentEdit(DocumentTemplateVariable $variable, ?int $templateId = null): self
    {
        return new self('value', $templateId);
    }
}
