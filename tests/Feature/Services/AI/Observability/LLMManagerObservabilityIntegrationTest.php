<?php

declare(strict_types=1);

use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\AiExecutionStatus;
use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\Routing\ProviderRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AI\FakeLLMProvider;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('ai.routing.document_classification', [
        'gemini',
        'ollama',
    ]);

    $this->request = new LLMRequest(
        prompt: 'Classify this document.',
        useCase: AIUseCase::DOCUMENT_CLASSIFICATION,
        capability: LLMCapability::STRUCTURED_OUTPUT,
        jsonSchema: [
            'type' => 'object',
        ],
    );
});

it('persists observability records through the container resolved manager', function (): void {
    $gemini = (new FakeLLMProvider('gemini'))
        ->willReturn(
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'gemini',
                model: 'fake-gemini',
                inputTokens: 100,
                outputTokens: 50,
            ),
        );

    $ollama = (new FakeLLMProvider('ollama'))
        ->willFail('Ollama should not be called.');

    $registry = app(ProviderRegistry::class);

    $registry->register($gemini);
    $registry->register($ollama);

    $manager = app(LLMManagerContract::class);

    $response = $manager->generate(
        $this->request,
    );

    expect($response->provider)
        ->toBe('gemini')
        ->and($gemini->callCount)
        ->toBe(1)
        ->and($ollama->callCount)
        ->toBe(0);

    $execution = AiExecution::query()->sole();

    expect($execution->status)
        ->toBe(AiExecutionStatus::SUCCEEDED)
        ->and($execution->use_case)
        ->toBe(AIUseCase::DOCUMENT_CLASSIFICATION)
        ->and($execution->capability)
        ->toBe(LLMCapability::STRUCTURED_OUTPUT)
        ->and($execution->attempt_count)
        ->toBe(1)
        ->and($execution->successful_provider)
        ->toBe('gemini')
        ->and($execution->successful_model)
        ->toBe('fake-gemini')
        ->and($execution->input_tokens)
        ->toBe(100)
        ->and($execution->output_tokens)
        ->toBe(50)
        ->and($execution->duration_ms)
        ->toBeGreaterThanOrEqual(0)
        ->and($execution->completed_at)
        ->not->toBeNull()
        ->and($execution->failed_at)
        ->toBeNull();

    $attempt = AiExecutionAttempt::query()->sole();

    expect($attempt->ai_execution_id)
        ->toBe($execution->getKey())
        ->and($attempt->sequence)
        ->toBe(1)
        ->and($attempt->provider)
        ->toBe('gemini')
        ->and($attempt->model)
        ->toBe('fake-model')
        ->and($attempt->status)
        ->toBe(AiExecutionStatus::SUCCEEDED)
        ->and($attempt->input_tokens)
        ->toBe(100)
        ->and($attempt->output_tokens)
        ->toBe(50)
        ->and($attempt->duration_ms)
        ->toBeGreaterThanOrEqual(0)
        ->and($attempt->completed_at)
        ->not->toBeNull()
        ->and($attempt->failed_at)
        ->toBeNull()
        ->and($attempt->exception_class)
        ->toBeNull()
        ->and($attempt->error_message)
        ->toBeNull();
});

it('persists failed and successful attempts during container resolved fallback', function (): void {
    $gemini = (new FakeLLMProvider('gemini'))
        ->willFail('Gemini unavailable.');

    $ollama = (new FakeLLMProvider('ollama'))
        ->willReturn(
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'ollama',
                model: 'fake-ollama',
                inputTokens: 150,
                outputTokens: 75,
            ),
        );

    $registry = app(ProviderRegistry::class);

    $registry->register($gemini);
    $registry->register($ollama);

    $manager = app(LLMManagerContract::class);

    $response = $manager->generate(
        $this->request,
    );

    expect($response->provider)
        ->toBe('ollama')
        ->and($gemini->callCount)
        ->toBe(1)
        ->and($ollama->callCount)
        ->toBe(1);

    $execution = AiExecution::query()->sole();

    expect($execution->status)
        ->toBe(AiExecutionStatus::SUCCEEDED)
        ->and($execution->attempt_count)
        ->toBe(2)
        ->and($execution->successful_provider)
        ->toBe('ollama')
        ->and($execution->successful_model)
        ->toBe('fake-ollama')
        ->and($execution->input_tokens)
        ->toBe(150)
        ->and($execution->output_tokens)
        ->toBe(75)
        ->and($execution->duration_ms)
        ->toBeGreaterThanOrEqual(0)
        ->and($execution->completed_at)
        ->not->toBeNull()
        ->and($execution->failed_at)
        ->toBeNull();

    $attempts = AiExecutionAttempt::query()
        ->orderBy('sequence')
        ->get();

    expect($attempts)
        ->toHaveCount(2);

    $failedAttempt = $attempts->get(0);

    expect($failedAttempt)
        ->not->toBeNull()
        ->and($failedAttempt->ai_execution_id)
        ->toBe($execution->getKey())
        ->and($failedAttempt->sequence)
        ->toBe(1)
        ->and($failedAttempt->provider)
        ->toBe('gemini')
        ->and($failedAttempt->model)
        ->toBe('fake-model')
        ->and($failedAttempt->status)
        ->toBe(AiExecutionStatus::FAILED)
        ->and($failedAttempt->duration_ms)
        ->toBeGreaterThanOrEqual(0)
        ->and($failedAttempt->completed_at)
        ->toBeNull()
        ->and($failedAttempt->failed_at)
        ->not->toBeNull()
        ->and($failedAttempt->exception_class)
        ->not->toBeNull()
        ->and($failedAttempt->error_message)
        ->toBe('Gemini unavailable.');

    $successfulAttempt = $attempts->get(1);

    expect($successfulAttempt)
        ->not->toBeNull()
        ->and($successfulAttempt->ai_execution_id)
        ->toBe($execution->getKey())
        ->and($successfulAttempt->sequence)
        ->toBe(2)
        ->and($successfulAttempt->provider)
        ->toBe('ollama')
        ->and($successfulAttempt->model)
        ->toBe('fake-model')
        ->and($successfulAttempt->status)
        ->toBe(AiExecutionStatus::SUCCEEDED)
        ->and($successfulAttempt->input_tokens)
        ->toBe(150)
        ->and($successfulAttempt->output_tokens)
        ->toBe(75)
        ->and($successfulAttempt->duration_ms)
        ->toBeGreaterThanOrEqual(0)
        ->and($successfulAttempt->completed_at)
        ->not->toBeNull()
        ->and($successfulAttempt->failed_at)
        ->toBeNull()
        ->and($successfulAttempt->exception_class)
        ->toBeNull()
        ->and($successfulAttempt->error_message)
        ->toBeNull();
});
