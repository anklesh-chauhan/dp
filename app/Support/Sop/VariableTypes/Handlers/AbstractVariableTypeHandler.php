<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\SopTemplateVariable;
use App\Support\Sop\VariableTypes\Contracts\VariableTypeHandler;
use Filament\Forms\Components\Field;

abstract class AbstractVariableTypeHandler implements VariableTypeHandler
{
    public function supports(string $code): bool
    {
        return in_array($code, $this->codes(), true);
    }

    protected function applyCommonConfiguration(Field $field, SopTemplateVariable $variable): Field
    {
        return $field
            ->label($variable->label)
            ->required($variable->required);
    }

    /**
     * @return array<string, string>
     */
    protected function choiceOptions(SopTemplateVariable $variable): array
    {
        if (is_array($variable->options) && $variable->options !== []) {
            return collect($variable->options)
                ->mapWithKeys(fn (mixed $label, int|string $value): array => [
                    (string) $value => (string) $label,
                ])
                ->all();
        }

        foreach ($this->normalizedCustomValidationRules($variable) as $rule) {
            if (! is_string($rule) || ! str_starts_with($rule, 'in:')) {
                continue;
            }

            return collect(explode(',', str($rule)->after('in:')->toString()))
                ->mapWithKeys(fn (string $option): array => [
                    trim($option) => str(trim($option))->replace('_', ' ')->title()->toString(),
                ])
                ->all();
        }

        return [];
    }

    /**
     * @return array<int, mixed>
     */
    protected function normalizedCustomValidationRules(SopTemplateVariable $variable): array
    {
        if (! is_array($variable->validation_rules)) {
            return [];
        }

        return collect($variable->validation_rules)
            ->map(function (mixed $value, int|string $key): mixed {
                if (is_int($key)) {
                    return $value;
                }

                return filled($value) ? "{$key}:{$value}" : $key;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    protected function mergeValidationRules(SopTemplateVariable $variable, array $baseRules): array
    {
        return array_values(array_unique(
            array_merge($baseRules, $this->normalizedCustomValidationRules($variable)),
            SORT_REGULAR,
        ));
    }

    protected function stringifyScalar(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        return (string) $value;
    }

    protected function formatBooleanForSubstitution(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
    }

    protected function parseBooleanDefault(?string $defaultValue): bool
    {
        return filter_var($defaultValue, FILTER_VALIDATE_BOOLEAN);
    }
}
