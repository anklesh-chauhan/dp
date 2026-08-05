<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\VariableDataType;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Contracts\TemplateGenerator;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final readonly class TemplateGeneratorService implements TemplateGenerator
{
    public function __construct(
        private LLMManagerContract $llmManager,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
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
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $generatedTemplate
     * @return array<string, mixed>|null
     */
    public function repairRegulatedTemplate(
        array $formData,
        string $regulationTags,
        array $generatedTemplate,
        string $validationError,
    ): ?array {
        try {
            $variableDataTypes = $this->authorizedVariableDataTypes();

            $response = $this->llmManager->generate(
                new LLMRequest(
                    prompt: $this->buildRepairPrompt(
                        formData: $formData,
                        regulationTags: $regulationTags,
                        generatedTemplate: $generatedTemplate,
                        validationError: $validationError,
                    ),
                    useCase: AIUseCase::REGULATED_TEMPLATE_GENERATION,
                    capability: LLMCapability::STRUCTURED_OUTPUT,
                    dataClassification: AIDataClassification::INTERNAL,
                    jsonSchema: $this->jsonSchema(
                        variableDataTypes: $variableDataTypes,
                    ),
                    temperature: 0.0,
                    metadata: [
                        'feature' => 'regulated_template_repair',
                    ],
                ),
            );

            return $response->structured();
        } catch (Throwable $exception) {
            Log::error(
                'Regulated Template Repair failed.',
                [
                    'exception' => $exception,
                    'template_name' => $formData['name'] ?? null,
                    'validation_error' => $validationError,
                ],
            );

            return null;
        }
    }

    public function generateSectionTitles(array $templateData, int $count): ?array
    {
        $response = $this->llmManager->generate(new LLMRequest(
            prompt: "Generate exactly {$count} concise QMS document section titles for this template:\n".json_encode($templateData, JSON_PRETTY_PRINT),
            useCase: AIUseCase::TEMPLATE_SECTION_GENERATION,
            capability: LLMCapability::STRUCTURED_OUTPUT,
            dataClassification: AIDataClassification::INTERNAL,
            jsonSchema: ['type' => 'object', 'properties' => ['titles' => ['type' => 'array', 'items' => ['type' => 'string']]], 'required' => ['titles']],
            temperature: 0.2,
            metadata: ['feature' => 'template_section_generation'],
        ));

        $titles = $response->structured()['titles'] ?? null;

        return is_array($titles) ? array_values(array_filter($titles, 'is_string')) : null;
    }

    public function completeSection(array $templateData, string $sectionTitle): ?string
    {
        $response = $this->llmManager->generate(new LLMRequest(
            prompt: "Write the complete reusable QMS section content for '{$sectionTitle}'. Return rich-editor-compatible HTML only, not Markdown. Use semantic tags such as <p>, <h3>, <ul>, <ol>, <li>, <strong>, and <em>; do not include <html>, <head>, or <body>. Return only the content. Template context:\n".json_encode($templateData, JSON_PRETTY_PRINT),
            useCase: AIUseCase::TEMPLATE_SECTION_COMPLETION,
            capability: LLMCapability::TEXT_GENERATION,
            dataClassification: AIDataClassification::INTERNAL,
            temperature: 0.2,
            metadata: ['feature' => 'template_section_completion'],
        ));

        return filled($response->content) ? trim($response->content) : null;
    }

    public function transformSectionContent(
        string $content,
        string $operation,
        string $sectionTitle,
        array $templateContext = [],
    ): ?array {
        $instruction = match ($operation) {
            'polish' => 'Polish and formalize the text using clear, professional pharmaceutical QMS language.',
            'shorten' => 'Shorten the text while preserving all important meaning, requirements, and controls.',
            default => "Create complete reusable content for the section titled '{$sectionTitle}'.",
        };

        $description = (string) ($templateContext['description'] ?? 'Not provided');
        $department = (string) ($templateContext['department'] ?? 'Not provided');
        $regulatoryTags = is_array($templateContext['regulatory_tags'] ?? null)
            ? implode(', ', $templateContext['regulatory_tags'])
            : (string) ($templateContext['regulatory_tags'] ?? 'Not provided');
        $existingVariables = json_encode(
            $templateContext['existing_variables'] ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ) ?: '[]';

        $prompt = <<<PROMPT
You are an expert pharmaceutical Quality Management System technical writer.

{$instruction}

Use the following document context to make the result specific, accurate, and consistent:

TEMPLATE DESCRIPTION:
{$description}

DEPARTMENT:
{$department}

REGULATORY TAGS:
{$regulatoryTags}

SECTION TITLE:
{$sectionTitle}

EXISTING SECTION CONTENT:
{$content}

EXISTING TEMPLATE VARIABLES:
{$existingVariables}

Requirements:
1. Preserve the intent and compliance meaning of the section.
2. Use formal, clear, audit-ready pharmaceutical QMS language.
3. Do not invent regulatory requirements that are not supported by the provided context.
4. Keep terminology consistent with the department and regulatory tags.
5. Return only the section content, without commentary or quotation marks.
6. The content is stored in a RichEditor. Return valid rich-editor-compatible HTML, not Markdown.
7. Use semantic HTML such as <p>, <h3>, <ul>, <ol>, <li>, <strong>, and <em>. Do not return Markdown markers, HTML document wrappers, or code fences.

8. Identify every variable placeholder needed in the content using {{variable_name}} syntax.
9. Reuse the existing variables listed in the context whenever they match.
10. Define only genuinely new variables, using concise snake_case names.
PROMPT;

        $response = $this->llmManager->generate(new LLMRequest(
            prompt: $prompt,
            useCase: AIUseCase::TEMPLATE_SECTION_COMPLETION,
            capability: LLMCapability::STRUCTURED_OUTPUT,
            dataClassification: AIDataClassification::INTERNAL,
            jsonSchema: [
                'type' => 'object',
                'properties' => [
                    'content' => ['type' => 'string'],
                    'variables' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'label' => ['type' => 'string'],
                                'datatype' => ['type' => 'string'],
                                'default_value' => ['type' => 'string'],
                                'required' => ['type' => 'boolean'],
                            ],
                            'required' => ['name', 'label', 'datatype', 'default_value', 'required'],
                        ],
                    ],
                ],
                'required' => ['content', 'variables'],
            ],
            temperature: 0.2,
            metadata: ['feature' => 'template_section_content_assistance', 'operation' => $operation],
        ));

        $result = $response->structured();

        return filled($result['content'] ?? null) && is_array($result['variables'] ?? null)
            ? ['content' => trim((string) $result['content']), 'variables' => $result['variables']]
            : null;
    }

    /**
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $generatedTemplate
     */
    private function buildRepairPrompt(
        array $formData,
        string $regulationTags,
        array $generatedTemplate,
        string $validationError,
    ): string {
        $name = $formData['name'] ?? '';
        $description = $formData['description'] ?? '';
        $classificationContext = $this->classificationContext($formData);

        $generatedTemplateJson = json_encode(
            $generatedTemplate,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if ($generatedTemplateJson === false) {
            throw new RuntimeException(
                'Unable to encode generated template for AI repair.',
            );
        }

        return <<<PROMPT
    You are repairing an invalid pharmaceutical and clinical Quality Management System template generated by an AI system.

    Do not generate an unrelated replacement template.

    Repair the provided template while preserving all valid sections, content, variables, regulatory context, and document intent wherever possible.

    TEMPLATE METADATA

    Name:
    {$name}

    Description:
    {$description}

    DOCUMENT CLASSIFICATION

    {$classificationContext}

    REGULATORY FRAMEWORKS

    {$regulationTags}

    VALIDATION FAILURE

    {$validationError}

    INVALID GENERATED TEMPLATE

    {$generatedTemplateJson}

    REPAIR REQUIREMENTS

    1. Correct the exact validation failure identified above.

    2. Preserve the independent document category and document type intent shown under DOCUMENT CLASSIFICATION.

    3. Preserve the structure appropriate to the document type unless changing it is required to correct the validation failure.

    4. Preserve valid generated content unless modification is required to restore consistency.

    5. Every generated variable must be referenced in the content of at least one relevant section.

    6. Variables must be inserted into section content using exactly this syntax:

    {{variable_name}}

    5. Every placeholder appearing in section content must have exactly one corresponding variable definition.

    6. Placeholder names must exactly match their corresponding variable names.

    7. Variable names must use snake_case.

    8. Do not remove a useful variable merely to bypass validation when it can be placed naturally into an appropriate section.

    9. Do not invent unnecessary variables.

    10. Preserve the intended section order and document structure.

    11. Select variable data types only from the authorized values enforced by the response schema.

    12. Return the complete repaired template, including both sections and variables.

    13. Return only the structured response matching the specified JSON schema.

    FINAL VALIDATION

    Before returning the repaired response:

    1. Verify every variable is referenced by at least one section.
    2. Verify every placeholder has a corresponding variable.
    3. Verify variable and placeholder names match exactly.
    4. Verify variable names use snake_case.
    5. Verify there are no duplicate variable names.
    6. Verify there are no orphan variables.
    7. Verify there are no undefined placeholders.
    PROMPT;
    }

    /**
     * @param  array<string, mixed>  $formData
     */
    private function buildPrompt(
        array $formData,
        string $regulationTags,
    ): string {
        $name = $formData['name'] ?? '';
        $description = $formData['description'] ?? '';
        $classificationContext = $this->classificationContext($formData);

        return <<<PROMPT
    You are an expert pharmaceutical and clinical Quality Management System auditor and technical writer.

    Generate a comprehensive corporate QMS template boilerplate based on the following user definitions.

    TEMPLATE METADATA

    Name:
    {$name}

    Description:
    {$description}

    DOCUMENT CLASSIFICATION

    {$classificationContext}

    REGULATORY FRAMEWORKS

    {$regulationTags}

    CRITICAL COMPLIANCE REQUIREMENTS

    1. The structure, sections, and variable parameters must comply with the applicable regulatory frameworks listed above.

    2. Treat document category and document type as independent classification dimensions.

    3. Use the document category to determine the operational subject matter and business context.

    4. Use the document type to determine structure, writing style, sections, and variable layout. For example:
       - SOP or work instruction: purpose, scope, responsibilities, prerequisites, sequential procedure, references, and revision history.
       - Form or checklist: structured completion fields, attestations, checks, and signatures.
       - Log or record: repeatable entry fields, dates, identifiers, performers, reviewers, and traceability.
       - Protocol: objective, scope, responsibilities, method, acceptance criteria, deviations, and approval.
       - Report: objective, method, results, analysis, conclusions, and approval.
       - Specification: requirements, test methods, limits, and acceptance criteria.

    5. Do not treat the category as restricting which document types are available.

    6. Generate practical QMS document sections appropriate for both the category and type.

    7. Do not invent regulatory requirements that cannot reasonably be inferred from the provided frameworks and template metadata.

    8. For each variable, select the most accurate data type from the authorized data types provided by the response schema.

    9. Variable names must be concise, descriptive, use snake_case, and be suitable for application-level identifiers.

    10. Every generated variable must be used in the content of at least one relevant section.

    11. Insert variables directly into section content using exactly this placeholder syntax:

    {{variable_name}}

    12. The placeholder name used in section content must exactly match the corresponding variable "name" returned in the variables array.

    13. Do not generate variables that are not referenced by at least one section.

    14. Do not reference placeholders in section content unless a corresponding variable exists in the variables array.

    15. Place each variable placeholder in the section where the value is contextually and operationally relevant.

    16. Write section content as an actual reusable document template. Do not merely describe what information the section should contain.

    17. When appropriate, embed placeholders naturally within sentences, paragraphs, headings, lists, or structured template content.

    18. A variable may be referenced multiple times when the same value is legitimately required in multiple sections.

    19. Section order values must represent the intended document sequence.

    20. Organize sections into a clear hierarchy for document navigation. Use heading_level 1 for major sections and heading_level 2 (or higher only when genuinely needed) for subsections.

    21. Set include_in_toc to true for substantive sections that should appear in a table of contents. Exclude purely administrative or helper sections only when appropriate.

    22. Provide toc_title only when the title shown in the table of contents should differ from the section title; otherwise return an empty string.

    23. Return the response matching the specified JSON structure.

    VARIABLE INTEGRITY RULE

    The following relationship must always be true:

    Every variables[*].name
        must have at least one matching
    {{variables[*].name}}
        placeholder somewhere in sections[*].content.

    Every {{placeholder}} appearing in sections[*].content
        must have exactly one corresponding variables[*].name.

    EXAMPLE

    If the variables array contains:

    {
        "name": "effective_date",
        "label": "Effective Date",
        "datatype": "date",
        "default_value": "",
        "required": true
    }

    Then a relevant section should contain content such as:

    "This procedure becomes effective on {{effective_date}}."

    Do not return an "effective_date" variable without using {{effective_date}} in relevant section content.

    FINAL VALIDATION

    Before returning the response:

    1. Verify that every generated variable is referenced in section content.
    2. Verify that every placeholder has a corresponding variable definition.
    3. Verify that placeholder names exactly match variable names.
    4. Verify that all variable names use snake_case.
    5. Verify that no orphan variables or undefined placeholders exist.

    Return only the structured response matching the specified JSON schema.
    PROMPT;
    }

    /**
     * @param  array<string, mixed>  $formData
     */
    private function classificationContext(array $formData): string
    {
        $category = is_array($formData['category'] ?? null)
            ? $formData['category']
            : [];
        $documentType = is_array($formData['document_type'] ?? null)
            ? $formData['document_type']
            : [];

        $categoryName = $this->classificationValue(
            $category['name'] ?? $formData['category_name'] ?? null,
        );
        $categoryCode = $this->classificationValue(
            $category['code'] ?? $formData['category_code'] ?? null,
        );
        $documentTypeName = $this->classificationValue(
            $documentType['name'] ?? $formData['document_type_name'] ?? null,
        );
        $documentTypeCode = $this->classificationValue(
            $documentType['code'] ?? $formData['document_type_code'] ?? null,
        );

        return <<<CONTEXT
    Category (operational subject): {$categoryName} [{$categoryCode}]
    Type (document structure): {$documentTypeName} [{$documentTypeCode}]

    The category and type are independent. The category defines what the document is about; the type defines how the document must be structured.
    CONTEXT;
    }

    private function classificationValue(mixed $value): string
    {
        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : 'Not specified';
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
     * @param  array<int, string>  $variableDataTypes
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
                                ],
                            ],

                            'heading_level' => [
                                'type' => 'integer',
                                'minimum' => 1,
                                'maximum' => 6,
                            ],

                            'include_in_toc' => [
                                'type' => 'boolean',
                            ],

                            'toc_title' => [
                                'type' => 'string',
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
