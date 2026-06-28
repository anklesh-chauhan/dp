<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Models\SopTemplateVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class VariableResolverService
{
    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     *
     * @throws ValidationException
     */
    public function resolveValues(SopTemplateVersion $version, array $values): array
    {
        $version->loadMissing('variables');

        $rawValues = $version->variables
            ->mapWithKeys(fn ($variable): array => [
                $variable->name => $values[$variable->name] ?? $variable->default_value,
            ])
            ->all();

        $this->validateRequiredVariables($version->variables, $rawValues);

        $resolved = collect($rawValues)
            ->map(fn (mixed $value): string => $this->stringifyValue($value))
            ->all();

        return $this->resolveNestedValues($resolved);
    }

    /**
     * @param  array<string, string>  $values
     */
    public function replace(string $content, array $values): string
    {
        return preg_replace_callback('/{{\s*([A-Za-z0-9_.-]+)\s*}}/', function (array $matches) use ($values): string {
            return (string) data_get($values, $matches[1], $matches[0]);
        }, $content) ?? $content;
    }

    /**
     * @param  Collection<int, mixed>  $variables
     * @param  array<string, string>  $values
     *
     * @throws ValidationException
     */
    private function validateRequiredVariables(Collection $variables, array $values): void
    {
        $rules = $variables
            ->mapWithKeys(function ($variable): array {
                $rule = $variable->required ? ['required'] : ['nullable'];

                if (is_array($variable->validation_rules)) {
                    $rule = array_merge($rule, $this->normalizeValidationRules($variable->validation_rules));
                }

                return [$variable->name => $rule];
            })
            ->all();

        Validator::make($values, $rules)->validate();
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private function resolveNestedValues(array $values): array
    {
        for ($pass = 0; $pass < 5; $pass++) {
            $changed = false;

            foreach ($values as $key => $value) {
                $resolved = $this->replace($value, $values);

                if ($resolved !== $value) {
                    $values[$key] = $resolved;
                    $changed = true;
                }
            }

            if (! $changed) {
                return $values;
            }
        }

        throw new InvalidArgumentException('Nested SOP variables exceed the supported resolution depth.');
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    /**
     * @param  array<int|string, mixed>  $rules
     * @return array<int, mixed>
     */
    private function normalizeValidationRules(array $rules): array
    {
        return collect($rules)
            ->map(function (mixed $value, int|string $key): mixed {
                if (is_int($key)) {
                    return $value;
                }

                return filled($value) ? "{$key}:{$value}" : $key;
            })
            ->values()
            ->all();
    }
}
