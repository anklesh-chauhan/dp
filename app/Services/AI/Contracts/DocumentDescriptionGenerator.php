<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

interface DocumentDescriptionGenerator
{
    /**
     * @return array{
     *     description: string|null,
     *     reasoning: string|null
     * }
     */
    public function generate(
        string $name,
        string $departmentName,
    ): array;
}
