<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Department;
use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\User;
use App\Models\VariableDataType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final readonly class AiDraftVariableNormalizer
{
    /**
     * Convert human-readable AI values to the canonical values expected by
     * the controlled-document variable resolver.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function normalize(DocumentTemplateVersion $version, array $values): array
    {
        $version->loadMissing('variables.variableDataType');

        return $version->variables
            ->mapWithKeys(fn (DocumentTemplateVariable $variable): array => [
                $variable->name => $this->normalizeValue(
                    $variable,
                    $values[$variable->name] ?? $variable->default_value ?? '',
                ),
            ])
            ->all();
    }

    private function normalizeValue(DocumentTemplateVariable $variable, mixed $value): mixed
    {
        if (blank($value)) {
            return '';
        }

        return match ($variable->variableDataType?->code) {
            VariableDataType::DEPARTMENT => $this->relationshipId(
                Department::query(),
                $value,
                ['name', 'code'],
            ),
            VariableDataType::USER, VariableDataType::EMPLOYEE => $this->relationshipId(
                User::query(),
                $value,
                ['name', 'email'],
            ),
            VariableDataType::SELECT, VariableDataType::RADIO => $this->choiceValue($variable, $value),
            default => $value,
        };
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<string>  $columns
     */
    private function relationshipId(Builder $query, mixed $value, array $columns): int|string
    {
        if (is_numeric($value)) {
            return $query->whereKey((int) $value)->value('id') ?? '';
        }

        $normalizedValue = Str::lower(trim((string) $value));

        foreach ($columns as $column) {
            $id = (clone $query)
                ->whereRaw("LOWER({$column}) = ?", [$normalizedValue])
                ->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return '';
    }

    private function choiceValue(DocumentTemplateVariable $variable, mixed $value): string
    {
        $normalizedValue = Str::lower(trim((string) $value));

        foreach ($variable->options ?? [] as $optionValue => $label) {
            if (
                Str::lower((string) $optionValue) === $normalizedValue
                || Str::lower((string) $label) === $normalizedValue
            ) {
                return (string) $optionValue;
            }
        }

        return '';
    }
}
