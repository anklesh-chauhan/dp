<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\Enums\ContentAssistFormat;
use App\Services\AI\Enums\ContentAssistOperation;

interface DocumentContentGenerator
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function transform(
        ContentAssistFormat $format,
        ContentAssistOperation $operation,
        string $content,
        array $context = [],
    ): ?string;
}
