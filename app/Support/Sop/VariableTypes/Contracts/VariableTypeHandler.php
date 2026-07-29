<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Contracts;

use App\Models\DocumentTemplateVariable;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;

interface VariableTypeHandler
{
    /**
     * @return list<string>
     */
    public function codes(): array;

    public function supports(string $code): bool;

    public function makeField(DocumentTemplateVariable $variable, VariableTypeFieldContext $context): Field;

    public function parseDefaultValue(?string $defaultValue): mixed;

    /**
     * @return array<int, mixed>
     */
    public function validationRules(DocumentTemplateVariable $variable): array;

    public function formatForStorage(DocumentTemplateVariable $variable, mixed $value): string;

    public function formatForSubstitution(DocumentTemplateVariable $variable, mixed $value): string;
}
