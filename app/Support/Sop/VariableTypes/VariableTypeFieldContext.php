<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes;

use App\Models\SopTemplateVariable;

final class VariableTypeFieldContext
{
    public function __construct(
        public readonly string $fieldName,
        public readonly ?int $templateId = null,
    ) {}

    public static function forDocumentCreation(SopTemplateVariable $variable, ?int $templateId): self
    {
        return new self("variables.{$variable->name}", $templateId);
    }

    public static function forDocumentEdit(SopTemplateVariable $variable, ?int $templateId = null): self
    {
        return new self('value', $templateId);
    }
}
