<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Contracts;

use App\Models\SopTemplateVariable;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;

interface VariableTypeHandler
{
    /**
     * @return list<string>
     */
    public function codes(): array;

    public function supports(string $code): bool;

    public function makeField(SopTemplateVariable $variable, VariableTypeFieldContext $context): Field;

    public function parseDefaultValue(?string $defaultValue): mixed;

    /**
     * @return array<int, mixed>
     */
    public function validationRules(SopTemplateVariable $variable): array;

    public function formatForStorage(SopTemplateVariable $variable, mixed $value): string;

    public function formatForSubstitution(SopTemplateVariable $variable, mixed $value): string;
}
