<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Validation\Rules;

use Anklesh\AiPlatform\Contracts\OutputRule;
use Anklesh\AiPlatform\Data\ValidationIssue;
use Anklesh\AiPlatform\Enums\ValidationSeverity;
use Illuminate\Support\Arr;
use InvalidArgumentException;

final readonly class RequiredPathRule implements OutputRule
{
    public function __construct(
        private string $path,
        private ValidationSeverity $severity = ValidationSeverity::Error,
    ) {
        if (trim($this->path) === '') {
            throw new InvalidArgumentException('The required output path may not be empty.');
        }
    }

    public function validate(array|string $output): iterable
    {
        if (is_array($output) && Arr::has($output, $this->path)) {
            $value = data_get($output, $this->path);

            if ($value !== null && $value !== '') {
                return [];
            }
        }

        return [new ValidationIssue(
            code: 'required_path',
            message: sprintf('The generated output must contain a value at [%s].', $this->path),
            path: $this->path,
            severity: $this->severity,
        )];
    }
}
