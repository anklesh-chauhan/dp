<?php

declare(strict_types=1);

use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Contracts\LLMProvider;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\AiExecutionStatus;
use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\Observability\DatabaseAiExecutionRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->recorder = new DatabaseAiExecutionRecorder();

    $this->request = new LLMRequest(
        prompt: 'Sensitive regulated document content.',
        useCase: AIUseCase::cases()[0],
        capability: LLMCapability::cases()[0],
        dataClassification: AIDataClassification::INTERNAL,
        jsonSchema: [
            'type' => 'object',
        ],
        systemPrompt: 'Sensitive system prompt.',
        metadata: [
            'secret' => 'sensitive-metadata',
        ],
    );
});

it('starts an execution without persisting request content', function (): void {
    $execution = $this->recorder->startExecution($this->request);

    expect($execution)
        ->toBeInstanceOf(AiExecution::class)
        ->and($execution->status)
        ->toBe(AiExecutionStatus::RUNNING)
        ->and($execution->attempt_count)
        ->toBe(0)
        ->and($execution->use_case)
        ->toBe($this->request->useCase)
        ->and($execution->capability)
        ->toBe($this->request->capability)
        ->and($execution->started_at)
        ->not->toBeNull()
        ->and($execution->completed_at)
        ->toBeNull()
        ->and($execution->failed_at)
        ->toBeNull();

    $attributes = $execution->getAttributes();

    expect($attributes)
        ->not->toHaveKey('prompt')
        ->not->toHaveKey('system_prompt')
        ->not->toHaveKey('json_schema')
        ->not->toHaveKey('metadata');
});

it('starts a provider attempt', function (): void {
    $execution = AiExecution::factory()->create();

    $provider = Mockery::mock(LLMProvider::class);

    $provider
        ->shouldReceive('name')
        ->once()
        ->andReturn('gemini');

    $provider
        ->shouldReceive('model')
        ->once()
        ->andReturn('gemini-test');

    $attempt = $this->recorder->startAttempt(
        $execution,
        $provider,
        1,
    );

    expect($attempt)
        ->toBeInstanceOf(AiExecutionAttempt::class)
        ->and($attempt->ai_execution_id)
        ->toBe($execution->getKey())
        ->and($attempt->sequence)
        ->toBe(1)
        ->and($attempt->provider)
        ->toBe('gemini')
        ->and($attempt->model)
        ->toBe('gemini-test')
        ->and($attempt->status)
        ->toBe(AiExecutionStatus::RUNNING)
        ->and($attempt->started_at)
        ->not->toBeNull()
        ->and($attempt->completed_at)
        ->toBeNull()
        ->and($attempt->failed_at)
        ->toBeNull();
});

it('completes a successful attempt', function (): void {
    $attempt = AiExecutionAttempt::factory()->create();

    $response = new LLMResponse(
        content: 'Sensitive generated content.',
        provider: 'gemini',
        model: 'gemini-test',
        inputTokens: 120,
        outputTokens: 80,
        durationMs: 999,
        metadata: [
            'secret' => 'sensitive-response-metadata',
        ],
    );

    $this->recorder->completeAttempt(
        $attempt,
        $response,
        250,
    );

    $attempt->refresh();

    expect($attempt->status)
        ->toBe(AiExecutionStatus::SUCCEEDED)
        ->and($attempt->input_tokens)
        ->toBe(120)
        ->and($attempt->output_tokens)
        ->toBe(80)
        ->and($attempt->duration_ms)
        ->toBe(250)
        ->and($attempt->completed_at)
        ->not->toBeNull()
        ->and($attempt->failed_at)
        ->toBeNull()
        ->and($attempt->exception_class)
        ->toBeNull()
        ->and($attempt->error_message)
        ->toBeNull();

    expect($attempt->getAttributes())
        ->not->toHaveKey('content')
        ->not->toHaveKey('metadata');
});

it('fails a provider attempt', function (): void {
    $attempt = AiExecutionAttempt::factory()->create();

    $exception = new RuntimeException('Provider unavailable.');

    $this->recorder->failAttempt(
        $attempt,
        $exception,
        300,
    );

    $attempt->refresh();

    expect($attempt->status)
        ->toBe(AiExecutionStatus::FAILED)
        ->and($attempt->input_tokens)
        ->toBeNull()
        ->and($attempt->output_tokens)
        ->toBeNull()
        ->and($attempt->duration_ms)
        ->toBe(300)
        ->and($attempt->exception_class)
        ->toBe(RuntimeException::class)
        ->and($attempt->error_message)
        ->toBe('Provider unavailable.')
        ->and($attempt->completed_at)
        ->toBeNull()
        ->and($attempt->failed_at)
        ->not->toBeNull();
});

it('completes a successful execution', function (): void {
    $execution = AiExecution::factory()->create();

    $response = new LLMResponse(
        content: [
            'sensitive' => 'generated-content',
        ],
        provider: 'ollama',
        model: 'qwen-test',
        inputTokens: 500,
        outputTokens: 250,
        durationMs: 999,
        metadata: [
            'secret' => 'sensitive-response-metadata',
        ],
    );

    $this->recorder->completeExecution(
        $execution,
        $response,
        2,
        700,
    );

    $execution->refresh();

    expect($execution->status)
        ->toBe(AiExecutionStatus::SUCCEEDED)
        ->and($execution->attempt_count)
        ->toBe(2)
        ->and($execution->successful_provider)
        ->toBe('ollama')
        ->and($execution->successful_model)
        ->toBe('qwen-test')
        ->and($execution->input_tokens)
        ->toBe(500)
        ->and($execution->output_tokens)
        ->toBe(250)
        ->and($execution->duration_ms)
        ->toBe(700)
        ->and($execution->completed_at)
        ->not->toBeNull()
        ->and($execution->failed_at)
        ->toBeNull();

    expect($execution->getAttributes())
        ->not->toHaveKey('content')
        ->not->toHaveKey('metadata');
});

it('fails an execution', function (): void {
    $execution = AiExecution::factory()->create([
        'successful_provider' => 'stale-provider',
        'successful_model' => 'stale-model',
        'input_tokens' => 100,
        'output_tokens' => 50,
        'completed_at' => now(),
    ]);

    $this->recorder->failExecution(
        $execution,
        2,
        800,
    );

    $execution->refresh();

    expect($execution->status)
        ->toBe(AiExecutionStatus::FAILED)
        ->and($execution->attempt_count)
        ->toBe(2)
        ->and($execution->successful_provider)
        ->toBeNull()
        ->and($execution->successful_model)
        ->toBeNull()
        ->and($execution->input_tokens)
        ->toBeNull()
        ->and($execution->output_tokens)
        ->toBeNull()
        ->and($execution->duration_ms)
        ->toBe(800)
        ->and($execution->completed_at)
        ->toBeNull()
        ->and($execution->failed_at)
        ->not->toBeNull();
});

it('records a failed execution with zero attempts', function (): void {
    $execution = AiExecution::factory()->create();

    $this->recorder->failExecution(
        $execution,
        0,
        10,
    );

    $execution->refresh();

    expect($execution->status)
        ->toBe(AiExecutionStatus::FAILED)
        ->and($execution->attempt_count)
        ->toBe(0)
        ->and($execution->attempts)
        ->toHaveCount(0);
});

it('uses centrally measured duration instead of provider duration', function (): void {
    $attempt = AiExecutionAttempt::factory()->create();

    $response = new LLMResponse(
        content: 'Generated content.',
        provider: 'gemini',
        model: 'gemini-test',
        durationMs: 9999,
    );

    $this->recorder->completeAttempt(
        $attempt,
        $response,
        125,
    );

    $attempt->refresh();

    expect($attempt->duration_ms)
        ->toBe(125);
});

it('normalizes negative durations and attempt counts', function (): void {
    $execution = AiExecution::factory()->create();

    $attempt = AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $execution->getKey(),
    ]);

    $response = new LLMResponse(
        content: 'Generated content.',
        provider: 'gemini',
        model: 'gemini-test',
    );

    $this->recorder->completeAttempt(
        $attempt,
        $response,
        -100,
    );

    $this->recorder->completeExecution(
        $execution,
        $response,
        -2,
        -500,
    );

    $attempt->refresh();
    $execution->refresh();

    expect($attempt->duration_ms)
        ->toBe(0)
        ->and($execution->attempt_count)
        ->toBe(0)
        ->and($execution->duration_ms)
        ->toBe(0);
});
