<?php

declare(strict_types=1);

use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
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

    $this->request = new LLMRequest(
        prompt: 'Classify this document.',
        useCase: AIUseCase::DOCUMENT_CLASSIFICATION,
        capability: LLMCapability::STRUCTURED_OUTPUT,
        jsonSchema: [
            'type' => 'object',
        ],
    );
});

it('returns providers in configured routing order', function (): void {
    $gemini = new FakeLLMProvider('gemini');
    $ollama = new FakeLLMProvider('ollama');

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $providers = $this->router->providersFor($this->request);

    expect($providers)
        ->toHaveCount(2)
        ->and($providers[0]->name())->toBe('gemini')
        ->and($providers[1]->name())->toBe('ollama');
});

it('skips providers that are not registered', function (): void {
    $ollama = new FakeLLMProvider('ollama');

    $this->registry->register($ollama);

    $providers = $this->router->providersFor($this->request);

    expect($providers)
        ->toHaveCount(1)
        ->and($providers[0]->name())->toBe('ollama');
});

it('skips providers that do not support the requested capability', function (): void {
    $gemini = new FakeLLMProvider(
        providerName: 'gemini',
        capabilities: [
            LLMCapability::TEXT_GENERATION,
        ],
    );

    $ollama = new FakeLLMProvider(
        providerName: 'ollama',
        capabilities: [
            LLMCapability::STRUCTURED_OUTPUT,
        ],
    );

    $this->registry->register($gemini);
    $this->registry->register($ollama);

    $providers = $this->router->providersFor($this->request);

    expect($providers)
        ->toHaveCount(1)
        ->and($providers[0]->name())->toBe('ollama');
});

it('returns an empty array when no eligible providers exist', function (): void {
    $providers = $this->router->providersFor($this->request);

    expect($providers)->toBe([]);
});
