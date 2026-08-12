<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\Enums\ApprovalNarrativeKind;
use App\Services\AI\Enums\ApprovalNarrativeOperation;

interface ApprovalNarrativeGenerator
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function transform(
        ApprovalNarrativeKind $kind,
        ApprovalNarrativeOperation $operation,
        string $content,
        array $context = [],
    ): ?string;
}
