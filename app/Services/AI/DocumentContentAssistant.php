<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\ProductModule;
use App\Services\AI\Contracts\DocumentContentGenerator;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\ContentAssistFormat;
use App\Services\AI\Enums\ContentAssistOperation;
use App\Services\AI\Enums\LLMCapability;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class DocumentContentAssistant implements DocumentContentGenerator
{
    public function __construct(
        private LLMManagerContract $llmManager,
        private ModuleManager $moduleManager,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transform(
        ContentAssistFormat $format,
        ContentAssistOperation $operation,
        string $content,
        array $context = [],
    ): ?string {
        $this->moduleManager->ensureEnabled(ProductModule::AI);

        $content = trim($content);

        if (
            $operation !== ContentAssistOperation::Create
            && $content === ''
        ) {
            return null;
        }

        try {
            $response = $this->llmManager->generate(
                new LLMRequest(
                    prompt: $this->buildPrompt(
                        format: $format,
                        operation: $operation,
                        content: $content,
                        context: $context,
                    ),
                    useCase: AIUseCase::DOCUMENT_CONTENT_ASSISTANCE,
                    capability: LLMCapability::STRUCTURED_OUTPUT,
                    dataClassification: AIDataClassification::INTERNAL,
                    jsonSchema: $this->jsonSchema(),
                    temperature: $operation === ContentAssistOperation::Polish
                        ? 0.1
                        : 0.2,
                    metadata: [
                        'feature' => 'document_content_assistance',
                        'format' => $format->value,
                        'operation' => $operation->value,
                    ],
                ),
            );

            $output = $response->structured();

            return $this->normalizeResult(
                $output['text'] ?? null,
                $format,
            );
        } catch (Throwable $exception) {
            Log::error('Document content AI assistance failed.', [
                'exception' => $exception,
                'format' => $format->value,
                'operation' => $operation->value,
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildPrompt(
        ContentAssistFormat $format,
        ContentAssistOperation $operation,
        string $content,
        array $context,
    ): string {
        $instruction = $operation->instruction($format);
        $fieldLabel = $this->contextLine($context, 'field_label', 'Document field');
        $sectionTitle = $this->contextLine($context, 'section_title', 'Not provided');
        $subject = $this->contextLine($context, 'subject', 'Not provided');
        $department = $this->contextLine($context, 'department', 'Not provided');
        $extra = $this->contextLine($context, 'extra', 'Not provided');
        $existing = $content !== '' ? $content : 'None provided yet.';
        $formatRules = match ($format) {
            ContentAssistFormat::Html => <<<'RULES'
5. Return valid rich-editor-compatible HTML, not Markdown.
6. Use semantic HTML such as <p>, <h3>, <ul>, <ol>, <li>, <strong>, and <em>.
7. Do not return Markdown markers, HTML document wrappers, or code fences.
RULES,
            ContentAssistFormat::Plain => <<<'RULES'
5. Return plain text only.
6. Do not use Markdown, HTML, bullet decoration, or quotation marks around the whole answer.
RULES,
        };

        return <<<PROMPT
You are an expert pharmaceutical Quality Management System technical writer.

{$instruction}

FIELD:
{$fieldLabel}

SECTION TITLE:
{$sectionTitle}

DOCUMENT / SUBJECT:
{$subject}

DEPARTMENT:
{$department}

ADDITIONAL CONTEXT:
{$extra}

EXISTING CONTENT:
{$existing}

REQUIREMENTS:
1. Preserve the intent and compliance meaning of the content.
2. Use formal, clear, audit-ready pharmaceutical QMS language.
3. Do not invent regulatory requirements that are not supported by the provided context.
4. Keep terminology consistent with the department and subject.
{$formatRules}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function contextLine(
        array $context,
        string $key,
        string $fallback,
    ): string {
        $value = $context[$key] ?? null;

        if (is_array($value)) {
            $value = implode(', ', array_map(
                static fn (mixed $item): string => trim((string) $item),
                $value,
            ));
        }

        if (! is_scalar($value) && $value !== null) {
            return $fallback;
        }

        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'text' => [
                    'type' => 'string',
                    'description' => 'The assisted document content.',
                ],
            ],
            'required' => ['text'],
        ];
    }

    private function normalizeResult(mixed $value, ContentAssistFormat $format): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if ($format === ContentAssistFormat::Plain) {
            $value = trim(html_entity_decode(strip_tags($value)));
        }

        return $value !== '' ? $value : null;
    }
}
