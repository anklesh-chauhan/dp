<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\ValueObjects;

use App\Foundation\AI\Validation\Enums\RuleCategory;
use App\Foundation\AI\Validation\Enums\RuleExecutionMode;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use App\Foundation\AI\Validation\Exceptions\InvalidRuleMetadataException;

/**
 * Immutable metadata describing a validation rule.
 */
final readonly class RuleMetadata
{
    public function __construct(
        public string $code,
        public string $name,
        public RuleCategory $category,
        public ValidationSeverity $defaultSeverity,
        public RuleExecutionMode $executionMode,
        public string $version,
    ) {
        if (trim($this->code) === '') {
            throw InvalidRuleMetadataException::emptyCode();
        }

        if (trim($this->name) === '') {
            throw InvalidRuleMetadataException::emptyName();
        }

        if (trim($this->version) === '') {
            throw InvalidRuleMetadataException::emptyVersion();
        }
    }
}
