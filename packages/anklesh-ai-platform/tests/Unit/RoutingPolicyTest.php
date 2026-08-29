<?php

declare(strict_types=1);

use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Enums\DataClassification;
use Anklesh\AiPlatform\Exceptions\InvalidRoutingConfiguration;
use Anklesh\AiPlatform\Exceptions\NoEligibleProvider;
use Anklesh\AiPlatform\Routing\RoutingPolicy;
use Illuminate\Config\Repository;

it('routes a use case through eligible providers in configured order', function () {
    $routing = new RoutingPolicy(new Repository([
        'anklesh-ai-platform' => [
            'routes' => [
                'default' => ['openai'],
                'summarization' => [
                    ['provider' => 'gemini', 'model' => 'gemini-fast'],
                    ['provider' => 'openai', 'model' => 'gpt-small'],
                    'ollama',
                ],
            ],
            'providers' => [
                'gemini' => [
                    'enabled' => true,
                    'classifications' => ['public', 'internal'],
                ],
                'openai' => [
                    'enabled' => true,
                    'classifications' => ['public'],
                ],
                'ollama' => [
                    'enabled' => false,
                    'classifications' => ['*'],
                ],
            ],
        ],
    ]));

    $routes = $routing->providersFor(new AiRequest(
        prompt: 'Summarize this.',
        useCase: 'summarization',
        classification: DataClassification::Internal,
    ));

    expect($routes)->toHaveCount(1)
        ->and($routes[0]->provider)->toBe('gemini')
        ->and($routes[0]->model)->toBe('gemini-fast');
});

it('falls back to the default route for an unknown use case', function () {
    $routing = new RoutingPolicy(new Repository([
        'anklesh-ai-platform' => [
            'routes' => [
                'default' => ['openai', 'gemini'],
            ],
            'providers' => [],
        ],
    ]));

    $routes = $routing->providersFor(new AiRequest(
        prompt: 'Write a title.',
        useCase: 'new_use_case',
    ));

    expect($routes)->toHaveCount(2)
        ->and($routes[0]->provider)->toBe('openai')
        ->and($routes[1]->provider)->toBe('gemini');
});

it('rejects a route when no provider is eligible', function () {
    $routing = new RoutingPolicy(new Repository([
        'anklesh-ai-platform' => [
            'routes' => ['default' => ['openai']],
            'providers' => [
                'openai' => [
                    'enabled' => true,
                    'classifications' => ['public'],
                ],
            ],
        ],
    ]));

    expect(fn () => $routing->providersFor(new AiRequest(
        prompt: 'Analyze private data.',
        classification: DataClassification::Restricted,
    )))->toThrow(NoEligibleProvider::class);
});

it('rejects malformed route configuration', function () {
    $routing = new RoutingPolicy(new Repository([
        'anklesh-ai-platform' => [
            'routes' => ['default' => [['model' => 'missing-provider']]],
            'providers' => [],
        ],
    ]));

    expect(fn () => $routing->providersFor(new AiRequest('Hello')))
        ->toThrow(InvalidRoutingConfiguration::class);
});
