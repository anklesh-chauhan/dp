<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Enums;

/**
 * Represents the severity level of a validation issue.
 *
 * The severity determines the impact of a validation issue on the
 * overall validation result.
 */
enum ValidationSeverity: string
{
    /**
     * Informational message that does not require action.
     */
    case INFO = 'info';

    /**
     * Indicates a non-blocking issue that should be reviewed.
     */
    case WARNING = 'warning';

    /**
     * Indicates a blocking validation failure.
     */
    case ERROR = 'error';

    /**
     * Indicates a critical failure that should immediately halt
     * further processing.
     */
    case CRITICAL = 'critical';

    /**
     * Determines whether this severity represents a blocking issue.
     */
    public function isBlocking(): bool
    {
        return match ($this) {
            self::INFO,
            self::WARNING => false,

            self::ERROR,
            self::CRITICAL => true,
        };
    }

    /**
     * Returns the numeric priority of the severity.
     *
     * Higher values indicate more severe issues.
     */
    public function priority(): int
    {
        return match ($this) {
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4,
        };
    }
}
