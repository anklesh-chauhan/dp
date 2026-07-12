<?php

declare(strict_types=1);

namespace App\Services\AI\Data;

final readonly class DocumentClassificationResult
{
    /**
     * @param array<int, int|string> $regulationTagIds
     */
    public function __construct(
        public int|string $documentCategoryId,
        public int|string $documentTypeId,
        public array $regulationTagIds,
        public string $provider,
        public string $model,
    ) {}

    /**
     * @return array{
     *     document_category_id: int|string,
     *     document_type_id: int|string,
     *     regulation_tag_ids: array<int, int|string>,
     *     ai_provider: string,
     *     ai_model: string
     * }
     */
    public function toArray(): array
    {
        return [
            'document_category_id' => $this->documentCategoryId,
            'document_type_id' => $this->documentTypeId,
            'regulation_tag_ids' => $this->regulationTagIds,
            'ai_provider' => $this->provider,
            'ai_model' => $this->model,
        ];
    }
}
