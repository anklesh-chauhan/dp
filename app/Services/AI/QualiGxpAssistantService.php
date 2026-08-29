<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Ai\Agents\QualiGxpAssistantAgent;
use App\Enums\ProductModule;
use App\Models\AiExecution;
use App\Models\User;
use App\Services\AI\Contracts\AiExecutionRecorder;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\Routing\AiProviderGovernance;
use App\Support\Modules\ModuleManager;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use Throwable;

final readonly class QualiGxpAssistantService
{
    public function __construct(
        private ModuleManager $moduleManager,
        private AiProviderGovernance $providerGovernance,
        private AiRequestRateLimiter $rateLimiter,
        private AiExecutionRecorder $recorder,
    ) {}

    /**
     * @param  Closure(string): void  $onDelta
     * @return array{message: string, conversation_id: string}
     */
    public function stream(
        User $user,
        string $message,
        Closure $onDelta,
        ?string $conversationId = null,
    ): array {
        $this->moduleManager->ensureEnabled(ProductModule::AI);

        if (Gate::forUser($user)->denies('Use:AiAssistant')) {
            throw new AuthorizationException('You do not have permission to use the AI assistant.');
        }

        $this->rateLimiter->ensureAvailable(
            'application-assistant',
            $user,
            (int) config('ai.governance.rate_limits.application_assistant', 12),
        );
        $providers = $this->providerGovernance->providersFor(
            AIUseCase::APPLICATION_ASSISTANT,
            AIDataClassification::INTERNAL,
        );

        if ($providers === []) {
            throw ValidationException::withMessages([
                'userMessage' => 'No approved AI provider is enabled for the application assistant.',
            ]);
        }

        if ($conversationId !== null) {
            $this->authorizeConversation($conversationId, $user);
        }

        $agent = QualiGxpAssistantAgent::make(user: $user);
        $agent = $conversationId === null
            ? $agent->forUser($user)
            : $agent->continue($conversationId, as: $user);
        $request = new LLMRequest(
            prompt: $message,
            useCase: AIUseCase::APPLICATION_ASSISTANT,
            capability: LLMCapability::TOOL_CALLING,
            dataClassification: AIDataClassification::INTERNAL,
            metadata: ['feature' => 'quali_gxp_application_assistant'],
        );
        $execution = $this->startExecution($request);
        $startedAt = hrtime(true);

        try {
            $response = $agent->stream(
                $message,
                provider: $providers,
                timeout: $this->timeout($providers),
            );
            $response->then(fn (StreamedAgentResponse $streamedResponse) => $this->completeExecution(
                $execution,
                $streamedResponse,
                $this->elapsedMilliseconds($startedAt),
            ));

            $lastMessageId = null;

            foreach ($response as $event) {
                if (! $event instanceof TextDelta) {
                    continue;
                }

                $separator = $lastMessageId !== null && $lastMessageId !== $event->messageId
                    ? "\n\n"
                    : '';

                $onDelta($separator.$event->delta);
                $lastMessageId = $event->messageId;
            }

            return [
                'message' => (string) $response->text,
                'conversation_id' => (string) $response->conversationId,
            ];
        } catch (Throwable $exception) {
            $this->failExecution($execution, $this->elapsedMilliseconds($startedAt));

            throw $exception;
        }
    }

    private function authorizeConversation(string $conversationId, User $user): void
    {
        $owned = Conversation::query()
            ->whereKey($conversationId)
            ->where('participant_type', $user->getMorphClass())
            ->where('participant_id', $user->getKey())
            ->exists();

        if (! $owned) {
            throw new AuthorizationException('You cannot access another user’s AI conversation.');
        }
    }

    /** @param list<string> $providers */
    private function timeout(array $providers): int
    {
        return max(array_map(
            fn (string $provider): int => (int) config("ai.providers.{$provider}.timeout", 120),
            $providers,
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

    private function completeExecution(?AiExecution $execution, AgentResponse $response, int $durationMs): void
    {
        if ($execution === null) {
            return;
        }

        try {
            $this->recorder->completeExecution(
                $execution,
                new LLMResponse(
                    content: $response->text,
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
            // Observability must not discard a valid read-only response.
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
            // Preserve the original provider failure.
        }
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return max(0, (int) ((hrtime(true) - $startedAt) / 1_000_000));
    }
}
