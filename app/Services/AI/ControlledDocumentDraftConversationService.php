<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Ai\Agents\ControlledDocumentDraftAgent;
use App\Enums\ProductModule;
use App\Models\AiExecution;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentDraftSession;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\AI\Contracts\AiExecutionRecorder;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\ControlledDocumentDraftSessionStatus;
use App\Services\AI\Enums\LLMCapability;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final readonly class ControlledDocumentDraftConversationService
{
    public function __construct(
        private ModuleManager $moduleManager,
        private AiExecutionRecorder $recorder,
    ) {}

    public function start(
        User $user,
        int $templateVersionId,
        int $ownerId,
        ?int $referencedControlledDocumentId = null,
    ): ControlledDocumentDraftSession {
        $this->authorize($user);

        $version = $this->publishedVersion($templateVersionId);

        if ($version->template->documentType->requiresSopReference() && $referencedControlledDocumentId === null) {
            throw ValidationException::withMessages([
                'referencedControlledDocumentId' => 'A referenced effective SOP is required for this template.',
            ]);
        }

        return ControlledDocumentDraftSession::query()->create([
            'created_by' => $user->getKey(),
            'template_id' => $version->document_template_id,
            'template_version_id' => $version->getKey(),
            'owner_id' => $ownerId,
            'referenced_controlled_document_id' => $referencedControlledDocumentId,
            'status' => ControlledDocumentDraftSessionStatus::GATHERING,
            'brief' => [],
            'draft_variables' => $version->variables
                ->mapWithKeys(fn ($variable): array => [
                    $variable->name => (string) ($variable->default_value ?? ''),
                ])
                ->all(),
        ])->load(['template.documentType', 'templateVersion.variables.variableDataType', 'owner']);
    }

    /**
     * @return array<string, mixed>
     */
    public function respond(
        ControlledDocumentDraftSession $session,
        User $user,
        string $message,
    ): array {
        $this->authorizeSession($session, $user);

        if (! $session->status->canChat()) {
            throw ValidationException::withMessages([
                'userMessage' => 'This draft conversation can no longer be changed.',
            ]);
        }

        $session->loadMissing([
            'template.documentType',
            'templateVersion.templateStatus',
            'templateVersion.sections',
            'templateVersion.variables.variableDataType',
        ]);

        $this->publishedVersion((int) $session->template_version_id);

        $definitions = $this->variableDefinitions($session->templateVersion);
        $agent = new ControlledDocumentDraftAgent(
            variableDefinitions: $definitions,
            templateContext: $this->templateContext($session),
            currentBrief: $session->brief ?? [],
        );

        $request = new LLMRequest(
            prompt: $message,
            useCase: AIUseCase::CONTROLLED_DOCUMENT_DRAFTING,
            capability: LLMCapability::STRUCTURED_OUTPUT,
            dataClassification: AIDataClassification::INTERNAL,
            metadata: [
                'feature' => 'controlled_document_draft_chat',
                'draft_session_uuid' => $session->uuid,
                'template_version_id' => $session->template_version_id,
            ],
        );

        $execution = $this->startExecution($request);
        $startedAt = hrtime(true);

        try {
            $response = $session->conversation_id === null
                ? $agent->forUser($user)->prompt(
                    $message,
                    provider: $this->providers(),
                    timeout: $this->timeout(),
                )
                : $agent->continue($session->conversation_id, as: $user)->prompt(
                    $message,
                    provider: $this->providers(),
                    timeout: $this->timeout(),
                );

            if (! $response instanceof StructuredAgentResponse) {
                throw new \RuntimeException('The drafting agent did not return structured content.');
            }

            $result = $this->normalizeResult($response->toArray(), $definitions, $session);
            $durationMs = $this->elapsedMilliseconds($startedAt);

            $session->forceFill([
                'conversation_id' => $response->conversationId ?? $session->conversation_id,
                'title' => $result['title'],
                'brief' => $result['brief'],
                'draft_variables' => $result['variables'],
                'status' => $result['ready_for_preview']
                    ? ControlledDocumentDraftSessionStatus::PREVIEW_READY
                    : ControlledDocumentDraftSessionStatus::GATHERING,
                'preview_revision' => $session->preview_revision + 1,
            ]);
            $session->preview_hash = $session->calculatePreviewHash();
            $session->save();

            $this->completeExecution($execution, $response, $result, $durationMs);

            return $result + [
                'preview_hash' => $session->preview_hash,
                'preview_revision' => $session->preview_revision,
            ];
        } catch (Throwable $exception) {
            $this->failExecution($execution, $this->elapsedMilliseconds($startedAt));

            throw $exception;
        }
    }

    private function authorize(User $user): void
    {
        $this->moduleManager->ensureEnabled(ProductModule::AI);
        Gate::forUser($user)->authorize('create', ControlledDocument::class);
    }

    private function authorizeSession(ControlledDocumentDraftSession $session, User $user): void
    {
        $this->authorize($user);

        if ((int) $session->created_by !== (int) $user->getKey()) {
            throw ValidationException::withMessages([
                'session' => 'You cannot access another user’s document draft.',
            ]);
        }
    }

    private function publishedVersion(int $id): DocumentTemplateVersion
    {
        $version = DocumentTemplateVersion::query()
            ->with([
                'template.documentType',
                'template.templateStatus',
                'templateStatus',
                'sections',
                'variables.variableDataType',
            ])
            ->findOrFail($id);

        if (
            ! $version->templateStatus?->hasCode(TemplateStatus::PUBLISHED)
            || ! $version->template->templateStatus?->hasCode(TemplateStatus::PUBLISHED)
        ) {
            throw ValidationException::withMessages([
                'templateVersionId' => 'Only published template versions can be used by the document assistant.',
            ]);
        }

        return $version;
    }

    /**
     * @return array<string, array{label: string, required: bool, default: string|null}>
     */
    private function variableDefinitions(DocumentTemplateVersion $version): array
    {
        return $version->variables
            ->mapWithKeys(fn ($variable): array => [
                $variable->name => [
                    'label' => $variable->label,
                    'required' => (bool) $variable->required,
                    'default' => $variable->default_value,
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function templateContext(ControlledDocumentDraftSession $session): array
    {
        return [
            'template_name' => $session->template->name,
            'template_code' => $session->template->code,
            'document_type' => $session->template->documentType->name,
            'template_version' => $session->templateVersion->version,
            'section_titles' => $session->templateVersion->sections->pluck('title')->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, array{label: string, required: bool, default: string|null}>  $definitions
     * @return array<string, mixed>
     */
    private function normalizeResult(
        array $result,
        array $definitions,
        ControlledDocumentDraftSession $session,
    ): array {
        $variables = [];
        $receivedVariables = is_array($result['variables'] ?? null) ? $result['variables'] : [];

        foreach ($definitions as $name => $definition) {
            $variables[$name] = trim((string) (
                $receivedVariables[$name]
                ?? $session->draft_variables[$name]
                ?? $definition['default']
                ?? ''
            ));
        }

        $missing = collect($definitions)
            ->filter(fn (array $definition, string $name): bool => $definition['required'] && blank($variables[$name]))
            ->map(fn (array $definition): string => $definition['label'])
            ->values()
            ->all();

        $title = trim((string) ($result['title'] ?? $session->title ?? ''));

        if ($title === '') {
            $missing[] = 'Document title';
        }

        return [
            'assistant_message' => trim((string) ($result['assistant_message'] ?? 'Please provide the missing document details.')),
            'title' => $title,
            'brief' => is_array($result['brief'] ?? null) ? $result['brief'] : ($session->brief ?? []),
            'variables' => $variables,
            'missing_details' => array_values(array_unique([
                ...$missing,
                ...array_map('strval', is_array($result['missing_details'] ?? null) ? $result['missing_details'] : []),
            ])),
            'ready_for_preview' => $missing === [] && (bool) ($result['ready_for_preview'] ?? false),
        ];
    }

    /**
     * @return list<string>
     */
    private function providers(): array
    {
        $providers = array_values(array_filter(
            config('ai.routing.controlled_document_drafting', []),
            fn (string $provider): bool => (bool) config("ai.providers.{$provider}.enabled", false),
        ));

        if ($providers === []) {
            throw ValidationException::withMessages([
                'provider' => 'No AI provider is enabled for controlled-document drafting.',
            ]);
        }

        return $providers;
    }

    private function timeout(): int
    {
        return max(array_map(
            fn (string $provider): int => (int) config("ai.providers.{$provider}.timeout", 120),
            $this->providers(),
        ));
    }

    private function startExecution(LLMRequest $request): ?AiExecution
    {
        try {
            return $this->recorder->startExecution($request);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function completeExecution(
        ?AiExecution $execution,
        StructuredAgentResponse $response,
        array $result,
        int $durationMs,
    ): void {
        if ($execution === null) {
            return;
        }

        try {
            $this->recorder->completeExecution(
                $execution,
                new LLMResponse(
                    content: $result,
                    provider: $response->meta->provider ?? 'unknown',
                    model: $response->meta->model ?? 'unknown',
                    inputTokens: $response->usage->promptTokens,
                    outputTokens: $response->usage->completionTokens,
                    durationMs: $durationMs,
                ),
                attemptCount: 1,
                durationMs: $durationMs,
            );
        } catch (Throwable) {
            // Observability must never prevent a user from retaining a valid AI draft.
        }
    }

    private function failExecution(?AiExecution $execution, int $durationMs): void
    {
        if ($execution === null) {
            return;
        }

        try {
            $this->recorder->failExecution($execution, 1, $durationMs);
        } catch (Throwable) {
            // The original provider exception remains the actionable failure.
        }
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return max(0, (int) ((hrtime(true) - $startedAt) / 1_000_000));
    }
}
