<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

interface DocumentClassifier
{
    /**
     * @return array{
     *     category_id: int|null,
     *     document_type_id: int|null,
     *     regulation_tag_ids: array<int>
     * }
     */
    public function classify(
        string $name,
        string $description,
        string $departmentName,
    ): array;
}
