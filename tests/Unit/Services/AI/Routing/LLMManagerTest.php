<?php

declare(strict_types=1);

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

    $this->manager = new LLMManager(
        $this->router,
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

    $this->manager->generate(
        $this->request,
    );
})->throws(AllProvidersFailedException::class);

it('throws when no eligible providers exist', function (): void {
    $this->manager->generate(
        $this->request,
    );
})->throws(AllProvidersFailedException::class);
