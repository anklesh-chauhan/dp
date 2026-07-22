<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Enums;

/**
 * Describes the preferred execution strategy for a validation rule.
 *
 * The execution mode is metadata only. It does not affect execution
 * until a scheduler or execution policy consumes it.
 */
enum RuleExecutionMode: string
{
    /**
     * Execute the rule as part of the normal validation flow.
     */
    case SEQUENTIAL = 'sequential';

    /**
     * The rule is suitable for parallel execution.
     */
    case PARALLEL = 'parallel';

    /**
     * The rule is computationally expensive and may be scheduled separately.
     */
    case EXPENSIVE = 'expensive';
}
