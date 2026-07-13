<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Services\AI\Contracts\DocumentClassifier;

final class DocumentAiClassifier implements DocumentClassifier
{
    public function __construct(
        private LLMManagerContract $llmManager,
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
        } catch (Throwable $exception) {
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

        if ($categories->count() === 1) {
            return $categories->first();
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

        $response = $this->llmManager->generate(
            new LLMRequest(
                prompt: $prompt,
                useCase: AIUseCase::DOCUMENT_CLASSIFICATION,
                capability: LLMCapability::STRUCTURED_OUTPUT,
                dataClassification: AIDataClassification::INTERNAL,
                jsonSchema: $jsonSchema,
                temperature: 0.1,
                metadata: [
                    'feature' => 'document_category_classification',
                ],
            ),
        );

        $output = $response->structured();

        $categoryCode = $output['category_code'] ?? null;

        if (! is_string($categoryCode)) {
            return null;
        }

        return $categories->first(
            fn (DocumentCategory $category): bool => $category->code === $categoryCode,
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

        $response = $this->llmManager->generate(
            new LLMRequest(
                prompt: $prompt,
                useCase: AIUseCase::DOCUMENT_TYPE_SELECTION,
                capability: LLMCapability::STRUCTURED_OUTPUT,
                dataClassification: AIDataClassification::INTERNAL,
                jsonSchema: $jsonSchema,
                temperature: 0.1,
                metadata: [
                    'feature' => 'document_type_classification',
                    'document_category_id' => (int) $category->getKey(),
                    'document_category_code' => $category->code,
                ],
            ),
        );

        $output = $response->structured();

        $documentTypeCode = $output['document_type_code'] ?? null;

        if (! is_string($documentTypeCode)) {
            return null;
        }

        return $documentTypes->first(
            fn (DocumentType $documentType): bool => $documentType->code === $documentTypeCode,
        );
    }

    /**
     * @return array{
     *     category_id: null,
     *     document_type_id: null,
     *     regulation_tag_ids: array<int>
     * }
     */
    private function emptyResult(): array
    {
        return [
            'category_id' => null,
            'document_type_id' => null,
            'regulation_tag_ids' => [],
        ];
    }
}
