<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\ValueObjects;

use App\Foundation\AI\Validation\Contracts\ValidationIssue;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;

/**
 * Immutable data object representing a validation issue.
 */
final readonly class ValidationIssueData implements ValidationIssue
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $code,
        private string $message,
        private ValidationSeverity $severity,
        private ?string $path = null,
        private array $metadata = [],
    ) {
    }

    /**
     * Creates an informational validation issue.
     *
     * @param array<string, mixed> $metadata
     */
    public static function info(
        string $code,
        string $message,
        ?string $path = null,
        array $metadata = [],
    ): self {
        return new self(
            code: $code,
            message: $message,
            severity: ValidationSeverity::INFO,
            path: $path,
            metadata: $metadata,
        );
    }

    /**
     * Creates a warning validation issue.
     *
     * @param array<string, mixed> $metadata
     */
    public static function warning(
        string $code,
        string $message,
        ?string $path = null,
        array $metadata = [],
    ): self {
        return new self(
            code: $code,
            message: $message,
            severity: ValidationSeverity::WARNING,
            path: $path,
            metadata: $metadata,
        );
    }

    /**
     * Creates an error validation issue.
     *
     * @param array<string, mixed> $metadata
     */
    public static function error(
        string $code,
        string $message,
        ?string $path = null,
        array $metadata = [],
    ): self {
        return new self(
            code: $code,
            message: $message,
            severity: ValidationSeverity::ERROR,
            path: $path,
            metadata: $metadata,
        );
    }

    /**
     * Creates a critical validation issue.
     *
     * @param array<string, mixed> $metadata
     */
    public static function critical(
        string $code,
        string $message,
        ?string $path = null,
        array $metadata = [],
    ): self {
        return new self(
            code: $code,
            message: $message,
            severity: ValidationSeverity::CRITICAL,
            path: $path,
            metadata: $metadata,
        );
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function severity(): ValidationSeverity
    {
        return $this->severity;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
