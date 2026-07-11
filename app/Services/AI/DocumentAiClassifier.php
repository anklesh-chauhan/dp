<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\DocumentType;
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
        $documentTypes = DocumentType::query()
            ->with(['category', 'regulationTags'])
            ->orderBy('name')
            ->get();

        if ($documentTypes->isEmpty()) {
            return $this->emptyResult();
        }

        $documentTypeOptions = $documentTypes
            ->mapWithKeys(
                fn (DocumentType $documentType): array => [
                    (string) $documentType->id => sprintf(
                        '%s | Category: %s',
                        $documentType->name,
                        $documentType->category?->name ?? 'Unknown',
                    ),
                ],
            )
            ->all();

        $prompt = <<<PROMPT
You are an expert pharmaceutical Quality Assurance and regulatory compliance auditor.

Analyze the SOP template metadata and select the single most appropriate document type from the authorized document types.

TEMPLATE METADATA

Name:
{$name}

Department:
{$departmentName}

Description:
{$description}

CLASSIFICATION RULES

1. Select exactly one document type.
2. Base the classification primarily on the operational purpose of the document.
3. Consider the department as supporting context.
4. Return only a document type ID available in the response schema.
5. Do not invent document types.
PROMPT;

        $jsonSchema = [
            'type' => 'object',
            'properties' => [
                'document_type_id' => [
                    'type' => 'integer',
                    'enum' => array_map(
                        'intval',
                        array_keys($documentTypeOptions),
                    ),
                    'description' => 'The ID of the best matching authorized document type.',
                ],
            ],
            'required' => [
                'document_type_id',
            ],
            'additionalProperties' => false,
        ];

        try {
            $output = $this->llmService->generateStructured(
                $prompt,
                $jsonSchema,
            );

            $documentTypeId = isset($output['document_type_id'])
                ? (int) $output['document_type_id']
                : null;

            if ($documentTypeId === null) {
                return $this->emptyResult();
            }

            /** @var DocumentType|null $documentType */
            $documentType = $documentTypes->firstWhere(
                'id',
                $documentTypeId,
            );

            if ($documentType === null) {
                return $this->emptyResult();
            }

            return [
                'category_id' => $documentType->category_id !== null
                    ? (int) $documentType->category_id
                    : null,

                'document_type_id' => (int) $documentType->getKey(),

                'regulation_tag_ids' => $documentType
                    ->regulationTags
                    ->modelKeys(),
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
