<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\DocumentCategory;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

final class DocumentAiClassifier
{
    public function __construct(
        private readonly LLMServiceInterface $llmService,
    ) {}

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
    ): array {
        try {
            $category = $this->classifyCategory(
                name: $name,
                description: $description,
                departmentName: $departmentName,
            );

            if ($category === null) {
                return $this->emptyResult();
            }

            $documentType = $this->classifyDocumentType(
                category: $category,
                name: $name,
                description: $description,
                departmentName: $departmentName,
            );

            if ($documentType === null) {
                return $this->emptyResult();
            }

            return [
                'category_id' => (int) $category->getKey(),

                'document_type_id' => (int) $documentType->getKey(),

                'regulation_tag_ids' => array_map(
                    'intval',
                    $documentType->regulationTags->modelKeys(),
                ),
            ];
        } catch (\Throwable $exception) {
            Log::error(
                'Document AI classification failed.',
                [
                    'exception' => $exception,
                    'name' => $name,
                    'department' => $departmentName,
                ],
            );

            return $this->emptyResult();
        }
    }

    private function classifyCategory(
    string $name,
    string $description,
    string $departmentName,
): ?DocumentCategory {
    $categories = DocumentCategory::query()
        ->orderBy('name')
        ->get();

    if ($categories->isEmpty()) {
        return null;
    }

    $categoryOptions = $categories
        ->mapWithKeys(
            fn (DocumentCategory $category): array => [
                $category->code => $category->name,
            ],
        )
        ->all();

    $authorizedCategories = collect($categoryOptions)
        ->map(
            fn (string $categoryName, string $code): string => sprintf(
                '%s: %s',
                $code,
                $categoryName,
            ),
        )
        ->implode("\n");

    $prompt = <<<PROMPT
You are an expert pharmaceutical Quality Assurance and regulatory compliance auditor.

Analyze the document metadata and select the single most appropriate document category.

DOCUMENT METADATA

Name:
{$name}

Department:
{$departmentName}

Description:
{$description}

AUTHORIZED DOCUMENT CATEGORIES

{$authorizedCategories}

CLASSIFICATION RULES

1. Select exactly one document category.
2. Classify primarily according to the operational purpose of the document.
3. Consider the department only as supporting context.
4. Return only a category code listed under AUTHORIZED DOCUMENT CATEGORIES.
5. Do not invent categories.
PROMPT;

    $jsonSchema = [
        'type' => 'object',

        'properties' => [
            'category_code' => [
                'type' => 'string',
                'enum' => array_keys($categoryOptions),
                'description' => 'The code of the best matching authorized document category.',
            ],
        ],

        'required' => [
            'category_code',
        ],
    ];

    $output = $this->llmService->generateStructured(
        $prompt,
        $jsonSchema,
    );

    $categoryCode = $output['category_code'] ?? null;

    if (! is_string($categoryCode)) {
        return null;
    }

    return $categories->first(
        fn (DocumentCategory $category): bool =>
            $category->code === $categoryCode,
    );
}

private function classifyDocumentType(
    DocumentCategory $category,
    string $name,
    string $description,
    string $departmentName,
): ?DocumentType {
    $documentTypes = DocumentType::query()
        ->with('regulationTags')
        ->whereBelongsTo($category, 'category')
        ->orderBy('name')
        ->get();

    if ($documentTypes->isEmpty()) {
        return null;
    }

    if ($documentTypes->count() === 1) {
        return $documentTypes->first();
    }

    $documentTypeOptions = $documentTypes
        ->mapWithKeys(
            fn (DocumentType $documentType): array => [
                $documentType->code => $documentType->name,
            ],
        )
        ->all();

    $authorizedDocumentTypes = collect($documentTypeOptions)
        ->map(
            fn (string $documentTypeName, string $code): string => sprintf(
                '%s: %s',
                $code,
                $documentTypeName,
            ),
        )
        ->implode("\n");

    $prompt = <<<PROMPT
You are an expert pharmaceutical Quality Assurance and regulatory compliance auditor.

The document has already been classified into this category:

{$category->name}

Analyze the document metadata and select the single most appropriate document type.

DOCUMENT METADATA

Name:
{$name}

Department:
{$departmentName}

Description:
{$description}

AUTHORIZED DOCUMENT TYPES

{$authorizedDocumentTypes}

CLASSIFICATION RULES

1. Select exactly one document type.
2. Select only from AUTHORIZED DOCUMENT TYPES.
3. Base classification primarily on the operational purpose of the document.
4. Consider the document name and description before the department.
5. Return only the document type code.
6. Do not invent document types.
PROMPT;

    $jsonSchema = [
        'type' => 'object',

        'properties' => [
            'document_type_code' => [
                'type' => 'string',
                'enum' => array_keys($documentTypeOptions),
                'description' => 'The code of the best matching authorized document type.',
            ],
        ],

        'required' => [
            'document_type_code',
        ],
    ];

    $output = $this->llmService->generateStructured(
        $prompt,
        $jsonSchema,
    );

    $documentTypeCode = $output['document_type_code'] ?? null;

    if (! is_string($documentTypeCode)) {
        return null;
    }

    return $documentTypes->first(
        fn (DocumentType $documentType): bool =>
            $documentType->code === $documentTypeCode,
    );
}
}
