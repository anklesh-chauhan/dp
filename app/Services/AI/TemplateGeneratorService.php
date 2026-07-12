<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\VariableDataType;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\Routing\LLMManager;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class TemplateGeneratorService
{
    public function __construct(
        private LLMManager $llmManager,
    ) {}

    /**
     * @param array<string, mixed> $formData
     *
     * @return array<string, mixed>|null
     */
    public function generateRegulatedTemplate(
        array $formData,
        string $regulationTags,
    ): ?array {
        try {
            $variableDataTypes = $this->authorizedVariableDataTypes();

            $response = $this->llmManager->generate(
                new LLMRequest(
                    prompt: $this->buildPrompt(
                        formData: $formData,
                        regulationTags: $regulationTags,
                    ),
                    useCase: AIUseCase::REGULATED_TEMPLATE_GENERATION,
                    capability: LLMCapability::STRUCTURED_OUTPUT,
                    dataClassification: AIDataClassification::INTERNAL,
                    jsonSchema: $this->jsonSchema(
                        variableDataTypes: $variableDataTypes,
                    ),
                    temperature: 0.1,
                    metadata: [
                        'feature' => 'regulated_template_generation',
                    ],
                ),
            );

            return $response->structured();
        } catch (Throwable $exception) {
            Log::error(
                'Regulated Template Generation failed.',
                [
                    'exception' => $exception,
                    'template_name' => $formData['name'] ?? null,
                ],
            );

            return null;
        }
    }

    /**
     * @param array<string, mixed> $formData
     */
    private function buildPrompt(
        array $formData,
        string $regulationTags,
    ): string {
        $name = $formData['name'] ?? '';
        $description = $formData['description'] ?? '';

        return <<<PROMPT
You are an expert pharmaceutical and clinical Quality Management System auditor and technical writer.

Generate a comprehensive corporate QMS template boilerplate based on the following user definitions.

TEMPLATE METADATA

Name:
{$name}

Description:
{$description}

REGULATORY FRAMEWORKS

{$regulationTags}

CRITICAL COMPLIANCE REQUIREMENTS

1. The structure, sections, and variable parameters must comply with the applicable regulatory frameworks listed above.
2. Generate practical QMS document sections appropriate for the template purpose.
3. Do not invent regulatory requirements that cannot reasonably be inferred from the provided frameworks and template metadata.
4. For each variable, select the most accurate data type from the authorized data types provided by the response schema.
5. Variable names must be concise, descriptive, and suitable for application-level identifiers.
6. Section order values must represent the intended document sequence.
7. Return the response matching the specified JSON structure.
PROMPT;
    }

    /**
     * @return array<int, string>
     */
    private function authorizedVariableDataTypes(): array
    {
        $variableDataTypes = VariableDataType::query()
            ->orderBy('name')
            ->pluck('code')
            ->filter(
                fn (mixed $code): bool => is_string($code) && $code !== '',
            )
            ->values()
            ->all();

        if ($variableDataTypes !== []) {
            return $variableDataTypes;
        }

        return [
            'text',
            'long_text',
            'integer',
            'boolean',
        ];
    }

    /**
     * @param array<int, string> $variableDataTypes
     *
     * @return array<string, mixed>
     */
    private function jsonSchema(array $variableDataTypes): array
    {
        return [
            'type' => 'object',

            'properties' => [
                'sections' => [
                    'type' => 'array',

                    'items' => [
                        'type' => 'object',

                        'properties' => [
                            'title' => [
                                'type' => 'string',
                            ],

                            'content' => [
                                'type' => 'string',
                            ],

                            'section_order' => [
                                'type' => 'integer',
                            ],

                            'section_type' => [
                                'type' => 'string',

                                'enum' => [
                                    'rich_text',
                                    'markdown',
                                    'text',
                                ],
                            ],
                        ],

                        'required' => [
                            'title',
                            'content',
                            'section_order',
                            'section_type',
                        ],
                    ],
                ],

                'variables' => [
                    'type' => 'array',

                    'items' => [
                        'type' => 'object',

                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],

                            'label' => [
                                'type' => 'string',
                            ],

                            'datatype' => [
                                'type' => 'string',
                                'enum' => $variableDataTypes,
                            ],

                            'default_value' => [
                                'type' => 'string',
                            ],

                            'required' => [
                                'type' => 'boolean',
                            ],
                        ],

                        'required' => [
                            'name',
                            'label',
                            'datatype',
                            'default_value',
                            'required',
                        ],
                    ],
                ],
            ],

            'required' => [
                'sections',
                'variables',
            ],
        ];
    }
}
