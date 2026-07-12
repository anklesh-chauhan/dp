<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

final class DocumentAiDescriptionGenerator
{
    public function __construct(
        private readonly LLMServiceInterface $llmService,
    ) {}

    /**
     * Generates a professional SOP template description based on name and department context.
     *
     * @return array{
     *     description: string|null,
     *     reasoning: string|null
     * }
     */
    public function generate(string $name, string $departmentName): array
    {
        if (blank($name)) {
            return $this->emptyResult();
        }

        $prompt = $this->buildPrompt($name, $departmentName);

        $jsonSchema = [
            'type' => 'object',
            'properties' => [
                'description' => [
                    'type' => 'string',
                    'description' => 'A comprehensive, clear, and professional Quality Management System (QMS) standard operating procedure description.',
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
            // 'additionalProperties' => false,
        ];

        try {
            $output = $this->llmService->generateStructured(
                $prompt,
                $jsonSchema,
            );

            return [
                'description' => isset($output['description']) ? trim((string) $output['description']) : null,
                'reasoning' => isset($output['reasoning']) ? trim((string) $output['reasoning']) : null,
            ];
        } catch (\Throwable $exception) {
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

    private function buildPrompt(string $name, string $departmentName): string
    {
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
4. Ensure the style matches international compliance frameworks (e.g., FDA, EMA, ISO 9001, or GxP guidelines where applicable).
5. Return the response matching the specified JSON structure.
PROMPT;
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
