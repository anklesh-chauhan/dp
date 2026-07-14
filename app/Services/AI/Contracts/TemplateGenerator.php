<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

interface TemplateGenerator
{
    /**
     * @param array<string, mixed> $formData
     *
     * @return array<string, mixed>|null
     */
    public function generateRegulatedTemplate(
        array $formData,
        string $regulationTags,
    ): ?array;

    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $generatedTemplate
     *
     * @return array<string, mixed>|null
     */
    public function repairRegulatedTemplate(
        array $formData,
        string $regulationTags,
        array $generatedTemplate,
        string $validationError,
    ): ?array;
}
