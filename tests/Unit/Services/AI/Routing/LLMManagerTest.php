<?php

declare(strict_types=1);

use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Contracts\AiExecutionRecorder;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\Exceptions\AllProvidersFailedException;
use App\Services\AI\Routing\LLMManager;
use App\Services\AI\Routing\ProviderRegistry;
use App\Services\AI\Routing\ProviderRouter;
use Tests\Support\AI\FakeLLMProvider;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('ai.routing.document_classification', [
        'gemini',
        'ollama',
    ]);

    $this->registry = new ProviderRegistry();

    $this->router = new ProviderRouter(
        $this->registry,
    );

    $this->recorder = Mockery::mock(
        AiExecutionRecorder::class,
    );

    $this->manager = new LLMManager(
        $this->router,
        $this->recorder,
    );

    $this->request = new LLMRequest(
        prompt: 'Classify this document.',
        useCase: AIUseCase::DOCUMENT_CLASSIFICATION,
        capability: LLMCapability::STRUCTURED_OUTPUT,
        jsonSchema: [
            'type' => 'object',
        ],
    );
});

it('returns the response from the first successful provider', function (): void {
    $gemini = (new FakeLLMProvider('gemini'))
        ->willReturn(
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'gemini',
                model: 'fake-gemini',
            ),
        );

    $ollama = (new FakeLLMProvider('ollama'))
        ->willReturn(
            new LLMResponse(
                content: [
                    'category_code' => 'RECORD',
                ],
                provider: 'ollama',
                model: 'fake-ollama',
            ),
        );

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $this->recorder->shouldIgnoreMissing();

    $response = $this->manager->generate(
        $this->request,
    );

    expect($response->provider)
        ->toBe('gemini')
        ->and($response->model)
        ->toBe('fake-gemini')
        ->and($response->structured())
        ->toBe([
            'category_code' => 'PROCEDURE',
        ])
        ->and($gemini->callCount)
        ->toBe(1)
        ->and($ollama->callCount)
        ->toBe(0);
});

it('falls back to the next provider when the first provider fails', function (): void {
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
            ),
        );

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $this->recorder->shouldIgnoreMissing();

    $response = $this->manager->generate(
        $this->request,
    );

    expect($response->provider)
        ->toBe('ollama')
        ->and($response->model)
        ->toBe('fake-ollama')
        ->and($response->structured())
        ->toBe([
            'category_code' => 'PROCEDURE',
        ])
        ->and($gemini->callCount)
        ->toBe(1)
        ->and($ollama->callCount)
        ->toBe(1);
});

it('does not call providers after a provider succeeds', function (): void {
    $gemini = (new FakeLLMProvider('gemini'))
        ->willReturn(
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'gemini',
                model: 'fake-gemini',
            ),
        );

    $ollama = (new FakeLLMProvider('ollama'))
        ->willFail('Ollama should not be called.');

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $this->recorder->shouldIgnoreMissing();

    $this->manager->generate(
        $this->request,
    );

    expect($gemini->callCount)
        ->toBe(1)
        ->and($ollama->callCount)
        ->toBe(0);
});

it('throws when all eligible providers fail', function (): void {
    $gemini = (new FakeLLMProvider('gemini'))
        ->willFail('Gemini unavailable.');

    $ollama = (new FakeLLMProvider('ollama'))
        ->willFail('Ollama unavailable.');

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $this->recorder->shouldIgnoreMissing();

    $this->manager->generate(
        $this->request,
    );
})->throws(AllProvidersFailedException::class);

it('throws when no eligible providers exist', function (): void {
    $this->recorder->shouldIgnoreMissing();

    $this->manager->generate(
        $this->request,
    );
})->throws(AllProvidersFailedException::class);

it('records a successful execution and provider attempt', function (): void {
    $execution = new AiExecution();

    $attempt = new AiExecutionAttempt();

    $response = new LLMResponse(
        content: [
            'category_code' => 'PROCEDURE',
        ],
        provider: 'gemini',
        model: 'fake-gemini',
        inputTokens: 100,
        outputTokens: 50,
    );

    $gemini = (new FakeLLMProvider('gemini'))
        ->willReturn($response);

    $this->registry->register($gemini);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->with($this->request)
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->with($execution, $gemini, 1)
        ->andReturn($attempt);

    $this->recorder
        ->shouldReceive('completeAttempt')
        ->once()
        ->with(
            $attempt,
            $response,
            Mockery::type('int'),
        );

    $this->recorder
        ->shouldReceive('completeExecution')
        ->once()
        ->with(
            $execution,
            $response,
            1,
            Mockery::type('int'),
        );

    $actualResponse = $this->manager->generate(
        $this->request,
    );

    expect($actualResponse)
        ->toBe($response)
        ->and($gemini->callCount)
        ->toBe(1);
});

it('records a failed attempt before successful fallback', function (): void {
    $execution = new AiExecution();

    $geminiAttempt = new AiExecutionAttempt();

    $ollamaAttempt = new AiExecutionAttempt();

    $gemini = (new FakeLLMProvider('gemini'))
        ->willFail('Gemini unavailable.');

    $response = new LLMResponse(
        content: [
            'category_code' => 'PROCEDURE',
        ],
        provider: 'ollama',
        model: 'fake-ollama',
    );

    $ollama = (new FakeLLMProvider('ollama'))
        ->willReturn($response);

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->with($execution, $gemini, 1)
        ->andReturn($geminiAttempt);

    $this->recorder
        ->shouldReceive('failAttempt')
        ->once()
        ->with(
            $geminiAttempt,
            Mockery::on(
                fn (Throwable $exception): bool => $exception->getMessage()
                    === 'Gemini unavailable.',
            ),
            Mockery::type('int'),
        );

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->with($execution, $ollama, 2)
        ->andReturn($ollamaAttempt);

    $this->recorder
        ->shouldReceive('completeAttempt')
        ->once()
        ->with(
            $ollamaAttempt,
            $response,
            Mockery::type('int'),
        );

    $this->recorder
        ->shouldReceive('completeExecution')
        ->once()
        ->with(
            $execution,
            $response,
            2,
            Mockery::type('int'),
        );

    $actualResponse = $this->manager->generate(
        $this->request,
    );

    expect($actualResponse)
        ->toBe($response)
        ->and($gemini->callCount)
        ->toBe(1)
        ->and($ollama->callCount)
        ->toBe(1);
});

it('records execution failure when all providers fail', function (): void {
    $execution = new AiExecution();

    $geminiAttempt = new AiExecutionAttempt();

    $ollamaAttempt = new AiExecutionAttempt();

    $gemini = (new FakeLLMProvider('gemini'))
        ->willFail('Gemini unavailable.');

    $ollama = (new FakeLLMProvider('ollama'))
        ->willFail('Ollama unavailable.');

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->with($execution, $gemini, 1)
        ->andReturn($geminiAttempt);

    $this->recorder
        ->shouldReceive('failAttempt')
        ->once()
        ->with(
            $geminiAttempt,
            Mockery::type(Throwable::class),
            Mockery::type('int'),
        );

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->with($execution, $ollama, 2)
        ->andReturn($ollamaAttempt);

    $this->recorder
        ->shouldReceive('failAttempt')
        ->once()
        ->with(
            $ollamaAttempt,
            Mockery::type(Throwable::class),
            Mockery::type('int'),
        );

    $this->recorder
        ->shouldReceive('failExecution')
        ->once()
        ->with(
            $execution,
            2,
            Mockery::type('int'),
        );

    $this->manager->generate(
        $this->request,
    );
})->throws(AllProvidersFailedException::class);

it('records a failed execution with zero attempts when no providers exist', function (): void {
    $execution = new AiExecution();

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('failExecution')
        ->once()
        ->with(
            $execution,
            0,
            Mockery::type('int'),
        );

    $this->manager->generate(
        $this->request,
    );
})->throws(AllProvidersFailedException::class);

it('continues generation when execution recording cannot start', function (): void {
    $response = new LLMResponse(
        content: [
            'category_code' => 'PROCEDURE',
        ],
        provider: 'gemini',
        model: 'fake-gemini',
    );

    $gemini = (new FakeLLMProvider('gemini'))
        ->willReturn($response);

    $this->registry->register($gemini);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andThrow(
            new RuntimeException(
                'Observability unavailable.',
            ),
        );

    $actualResponse = $this->manager->generate(
        $this->request,
    );

    expect($actualResponse)
        ->toBe($response)
        ->and($gemini->callCount)
        ->toBe(1);
});

it('continues provider execution when attempt recording cannot start', function (): void {
    $execution = new AiExecution();

    $response = new LLMResponse(
        content: [
            'category_code' => 'PROCEDURE',
        ],
        provider: 'gemini',
        model: 'fake-gemini',
    );

    $gemini = (new FakeLLMProvider('gemini'))
        ->willReturn($response);

    $this->registry->register($gemini);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->andThrow(
            new RuntimeException(
                'Attempt recording unavailable.',
            ),
        );

    $this->recorder
        ->shouldReceive('completeExecution')
        ->once()
        ->with(
            $execution,
            $response,
            1,
            Mockery::type('int'),
        );

    $actualResponse = $this->manager->generate(
        $this->request,
    );

    expect($actualResponse)
        ->toBe($response)
        ->and($gemini->callCount)
        ->toBe(1);
});

it('does not convert provider success into failure when attempt completion recording fails', function (): void {
    $execution = new AiExecution();

    $attempt = new AiExecutionAttempt();

    $response = new LLMResponse(
        content: [
            'category_code' => 'PROCEDURE',
        ],
        provider: 'gemini',
        model: 'fake-gemini',
    );

    $gemini = (new FakeLLMProvider('gemini'))
        ->willReturn($response);

    $this->registry->register($gemini);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->andReturn($attempt);

    $this->recorder
        ->shouldReceive('completeAttempt')
        ->once()
        ->andThrow(
            new RuntimeException(
                'Attempt completion unavailable.',
            ),
        );

    $this->recorder
        ->shouldReceive('completeExecution')
        ->once()
        ->andReturnNull();

    $actualResponse = $this->manager->generate(
        $this->request,
    );

    expect($actualResponse)
        ->toBe($response);
});

it('continues fallback when failed attempt recording fails', function (): void {
    $execution = new AiExecution();

    $geminiAttempt = new AiExecutionAttempt();

    $ollamaAttempt = new AiExecutionAttempt();

    $gemini = (new FakeLLMProvider('gemini'))
        ->willFail('Gemini unavailable.');

    $response = new LLMResponse(
        content: [
            'category_code' => 'PROCEDURE',
        ],
        provider: 'ollama',
        model: 'fake-ollama',
    );

    $ollama = (new FakeLLMProvider('ollama'))
        ->willReturn($response);

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->with($execution, $gemini, 1)
        ->andReturn($geminiAttempt);

    $this->recorder
        ->shouldReceive('failAttempt')
        ->once()
        ->andThrow(
            new RuntimeException(
                'Failure recording unavailable.',
            ),
        );

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->with($execution, $ollama, 2)
        ->andReturn($ollamaAttempt);

    $this->recorder
        ->shouldReceive('completeAttempt')
        ->once()
        ->andReturnNull();

    $this->recorder
        ->shouldReceive('completeExecution')
        ->once()
        ->andReturnNull();

    $actualResponse = $this->manager->generate(
        $this->request,
    );

    expect($actualResponse)
        ->toBe($response)
        ->and($gemini->callCount)
        ->toBe(1)
        ->and($ollama->callCount)
        ->toBe(1);
});

it('does not convert provider success into failure when execution completion recording fails', function (): void {
    $execution = new AiExecution();

    $attempt = new AiExecutionAttempt();

    $response = new LLMResponse(
        content: [
            'category_code' => 'PROCEDURE',
        ],
        provider: 'gemini',
        model: 'fake-gemini',
    );

    $gemini = (new FakeLLMProvider('gemini'))
        ->willReturn($response);

    $this->registry->register($gemini);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->andReturn($attempt);

    $this->recorder
        ->shouldReceive('completeAttempt')
        ->once()
        ->andReturnNull();

    $this->recorder
        ->shouldReceive('completeExecution')
        ->once()
        ->andThrow(
            new RuntimeException(
                'Execution completion unavailable.',
            ),
        );

    $actualResponse = $this->manager->generate(
        $this->request,
    );

    expect($actualResponse)
        ->toBe($response);
});

it('preserves all providers failed exception when execution failure recording fails', function (): void {
    $execution = new AiExecution();

    $attempt = new AiExecutionAttempt();

    $gemini = (new FakeLLMProvider('gemini'))
        ->willFail('Gemini unavailable.');

    $this->registry->register($gemini);

    $this->recorder
        ->shouldReceive('startExecution')
        ->once()
        ->andReturn($execution);

    $this->recorder
        ->shouldReceive('startAttempt')
        ->once()
        ->andReturn($attempt);

    $this->recorder
        ->shouldReceive('failAttempt')
        ->once()
        ->andReturnNull();

    $this->recorder
        ->shouldReceive('failExecution')
        ->once()
        ->andThrow(
            new RuntimeException(
                'Execution failure recording unavailable.',
            ),
        );

    $this->manager->generate(
        $this->request,
    );
})->throws(AllProvidersFailedException::class);
