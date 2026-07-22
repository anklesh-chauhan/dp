<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Enums;

/**
 * Categorises validation rules for reporting, organisation, and configuration.
 */
enum RuleCategory: string
{
    /**
     * Rules that validate the structural integrity of an artifact.
     */
    case STRUCTURE = 'structure';

    /**
     * Rules that validate the correctness of data values.
     */
    case DATA = 'data';

    /**
     * Rules that validate formatting requirements.
     */
    case FORMAT = 'format';

    /**
     * Rules that validate consistency across an artifact.
     */
    case CONSISTENCY = 'consistency';

    /**
     * Rules that validate relationships between values.
     */
    case RELATIONSHIP = 'relationship';

    /**
     * Rules that validate security-related constraints.
     */
    case SECURITY = 'security';

    /**
     * Rules that do not fit a predefined category.
     */
    case CUSTOM = 'custom';
}
