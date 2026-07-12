<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\DocumentDescriptionGenerator;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\Routing\LLMManager;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class DocumentAiDescriptionGenerator implements DocumentDescriptionGenerator
{
    public function __construct(
        private LLMManager $llmManager,
    ) {}

    /**
     * Generates a professional SOP template description based on
     * the document name and department context.
     *
     * @return array{
     *     description: string|null,
     *     reasoning: string|null
     * }
     */
    public function generate(
        string $name,
        string $departmentName,
    ): array {
        if (blank($name)) {
            return $this->emptyResult();
        }

        $prompt = $this->buildPrompt(
            name: $name,
            departmentName: $departmentName,
        );

        $jsonSchema = $this->jsonSchema();

        try {
            $response = $this->llmManager->generate(
                new LLMRequest(
                    prompt: $prompt,
                    useCase: AIUseCase::DOCUMENT_DESCRIPTION_GENERATION,
                    capability: LLMCapability::STRUCTURED_OUTPUT,
                    dataClassification: AIDataClassification::INTERNAL,
                    jsonSchema: $jsonSchema,
                    temperature: 0.2,
                    metadata: [
                        'feature' => 'document_description_generation',
                    ],
                ),
            );

            $output = $response->structured();

            return [
                'description' => $this->normalizeString(
                    $output['description'] ?? null,
                ),
                'reasoning' => $this->normalizeString(
                    $output['reasoning'] ?? null,
                ),
            ];
        } catch (Throwable $exception) {
            Log::error(
                'Document AI description generation failed.',
                [
                    'exception' => $exception,
                    'name' => $name,
                    'department' => $departmentName,
                ],
            );

            return $this->emptyResult();
        }
    }

    private function buildPrompt(
        string $name,
        string $departmentName,
    ): string {
        return <<<PROMPT
You are an expert pharmaceutical Quality Assurance consultant and QMS technical writer.

Draft a professional, compliant, and concise summary description for a standard operating procedure (SOP) template based on its title and operational department.

TEMPLATE METADATA

Name/Title:
{$name}

Department:
{$departmentName}

GENERATION RULES

1. Focus on operational compliance, clarity, and clear bounds of responsibility.
2. Outline the primary "why" and "what" of the procedure without inventing arbitrary technical step details.
3. Keep the output professional, objective, and clear of marketing fluff.
4. Ensure the style matches international compliance frameworks such as FDA, EMA, ISO 9001, or GxP guidelines where applicable.
5. Return the response matching the specified JSON structure.
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonSchema(): array
    {
        return [
            'type' => 'object',

            'properties' => [
                'description' => [
                    'type' => 'string',
                    'description' => 'A comprehensive, clear, and professional Quality Management System standard operating procedure description.',
                ],

                'reasoning' => [
                    'type' => 'string',
                    'description' => 'A brief explanation of why this description suits the given regulatory department scope.',
                ],
            ],

            'required' => [
                'description',
                'reasoning',
            ],
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

    /**
     * @return array{
     *     description: null,
     *     reasoning: null
     * }
     */
    private function emptyResult(): array
    {
        return [
            'description' => null,
            'reasoning' => null,
        ];
    }
}
