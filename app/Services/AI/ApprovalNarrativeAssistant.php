<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\ProductModule;
use App\Services\AI\Contracts\ApprovalNarrativeGenerator;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\ApprovalNarrativeKind;
use App\Services\AI\Enums\ApprovalNarrativeOperation;
use App\Services\AI\Enums\LLMCapability;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ApprovalNarrativeAssistant implements ApprovalNarrativeGenerator
{
    public function __construct(
        private LLMManagerContract $llmManager,
        private ModuleManager $moduleManager,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transform(
        ApprovalNarrativeKind $kind,
        ApprovalNarrativeOperation $operation,
        string $content,
        array $context = [],
    ): ?string {
        $this->moduleManager->ensureEnabled(ProductModule::AI);

        $content = trim($content);

        if (
            $operation !== ApprovalNarrativeOperation::Create
            && $content === ''
        ) {
            return null;
        }

        try {
            $response = $this->llmManager->generate(
                new LLMRequest(
                    prompt: $this->buildPrompt(
                        kind: $kind,
                        operation: $operation,
                        content: $content,
                        context: $context,
                    ),
                    useCase: $kind->useCase(),
                    capability: LLMCapability::STRUCTURED_OUTPUT,
                    dataClassification: AIDataClassification::INTERNAL,
                    jsonSchema: $this->jsonSchema(),
                    temperature: $operation === ApprovalNarrativeOperation::Polish
                        ? 0.1
                        : 0.2,
                    metadata: [
                        'feature' => 'approval_narrative_assistance',
                        'kind' => $kind->value,
                        'operation' => $operation->value,
                    ],
                ),
            );

            $output = $response->structured();

            return $this->normalizeString($output['text'] ?? null);
        } catch (Throwable $exception) {
            Log::error('Approval narrative AI assistance failed.', [
                'exception' => $exception,
                'kind' => $kind->value,
                'operation' => $operation->value,
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildPrompt(
        ApprovalNarrativeKind $kind,
        ApprovalNarrativeOperation $operation,
        string $content,
        array $context,
    ): string {
        $instruction = $operation->instruction($kind);
        $purpose = $kind->purpose();
        $subject = $this->contextLine($context, 'subject', 'Not provided');
        $recordType = $this->contextLine($context, 'record_type', 'Not provided');
        $decision = $this->contextLine($context, 'decision', 'Not provided');
        $department = $this->contextLine($context, 'department', 'Not provided');
        $extra = $this->contextLine($context, 'extra', 'Not provided');
        $existing = $content !== '' ? $content : 'None provided yet.';

        return <<<PROMPT
You are an expert pharmaceutical Quality Management System reviewer and technical writer.

{$instruction}

PURPOSE OF THIS FIELD:
{$purpose}

RECORD TYPE:
{$recordType}

SUBJECT:
{$subject}

DEPARTMENT:
{$department}

DECISION OR ACTION:
{$decision}

ADDITIONAL CONTEXT:
{$extra}

EXISTING TEXT:
{$existing}

REQUIREMENTS:
1. Use formal, clear, audit-ready pharmaceutical QMS language.
2. Do not invent facts, findings, regulatory citations, or review work that is not supported by the provided context.
3. Keep the result attributable and suitable for a permanent signed record.
4. Prefer concrete, review-focused wording over marketing language.
5. Keep the result under 2,000 characters.
6. Return plain text only. Do not use Markdown, HTML, bullet markers as decoration, or quotation marks around the whole answer.
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
                    'description' => 'The assisted approval narrative text.',
                ],
            ],
            'required' => ['text'],
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 2_000);
    }
}
