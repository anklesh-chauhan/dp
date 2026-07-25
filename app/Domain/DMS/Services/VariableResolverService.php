<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\SopTemplateVariable;
use App\Models\SopTemplateVersion;
use App\Support\Sop\VariableTypes\VariableTypeRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class VariableResolverService
{
    public function __construct(
        private readonly VariableTypeRegistry $variableTypeRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     * @return array{storage: array<string, string>, substitution: array<string, string>}
     *
     * @throws ValidationException
     */
    public function resolveValues(SopTemplateVersion $version, array $values): array
    {
        $version->loadMissing('variables.variableDataType');

        $rawValues = $version->variables
            ->mapWithKeys(fn (SopTemplateVariable $variable): array => [
                $variable->name => $values[$variable->name] ?? $this->variableTypeRegistry->parseDefaultValue($variable),
            ])
            ->all();

        $this->validateRequiredVariables($version->variables, $rawValues);

        $storage = $version->variables
            ->mapWithKeys(fn (SopTemplateVariable $variable): array => [
                $variable->name => $this->variableTypeRegistry->formatForStorage(
                    $variable,
                    $rawValues[$variable->name] ?? null,
                ),
            ])
            ->all();

        $substitution = $version->variables
            ->mapWithKeys(fn (SopTemplateVariable $variable): array => [
                $variable->name => $this->variableTypeRegistry->formatForSubstitution(
                    $variable,
                    $rawValues[$variable->name] ?? null,
                ),
            ])
            ->all();

        return [
            'storage' => $this->resolveNestedValues($storage),
            'substitution' => $this->resolveNestedValues($substitution),
        ];
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
     * @param  Collection<int, SopTemplateVariable>  $variables
     * @param  array<string, mixed>  $values
     *
     * @throws ValidationException
     */
    private function validateRequiredVariables(Collection $variables, array $values): void
    {
        $rules = $variables
            ->mapWithKeys(function (SopTemplateVariable $variable): array {
                $typeRules = array_values(array_filter(
                    $this->variableTypeRegistry->validationRules($variable),
                    fn (mixed $rule): bool => $rule !== 'nullable',
                ));

                $presenceRules = $variable->required ? ['required'] : ['nullable'];

                return [
                    $variable->name => array_merge($presenceRules, $typeRules),
                ];
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
}
