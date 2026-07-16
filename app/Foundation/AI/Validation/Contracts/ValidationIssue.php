<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Contracts;

use App\Foundation\AI\Validation\Enums\ValidationSeverity;

/**
 * Represents a single issue discovered during validation.
 *
 * Implementations should be immutable.
 */
interface ValidationIssue
{
    /**
     * Unique machine-readable error code.
     *
     * Example:
     *  - missing_title
     *  - invalid_json
     *  - duplicate_variable
     */
    public function code(): string;

    /**
     * Human-readable description of the validation issue.
     */
    public function message(): string;

    /**
     * Severity of the validation issue.
     */
    public function severity(): ValidationSeverity;

    /**
     * Path to the affected element.
     *
     * Examples:
     *  - title
     *  - sections[0].name
     *  - variables[3].datatype
     *
     * Returns null when the issue applies to the entire artifact.
     */
    public function path(): ?string;

    /**
     * Additional structured metadata describing the issue.
     *
     * This should contain machine-readable information only.
     */
    public function metadata(): array;
}
