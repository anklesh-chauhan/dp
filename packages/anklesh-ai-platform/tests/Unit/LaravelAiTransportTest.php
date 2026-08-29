<?php

declare(strict_types=1);

use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Data\ProviderRoute;
use Anklesh\AiPlatform\Transports\LaravelAiTransport;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Laravel\Ai\AiServiceProvider;
use Laravel\Ai\AnonymousAgent;

afterEach(function () {
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    Container::setInstance(null);
});

it('maps a Laravel AI agent response into the platform response', function () {
    $application = new Application(dirname(__DIR__, 4));
    $configuration = new Repository([
        'ai' => [
            'default' => 'openai',
            'providers' => [
                'openai' => [
                    'driver' => 'openai',
                    'key' => 'test-key',
                    'models' => [
                        'text' => ['default' => 'test-model'],
                    ],
                ],
            ],
        ],
    ]);
    $application->instance('config', $configuration);
    $application->instance(RepositoryContract::class, $configuration);
    $application->instance(DispatcherContract::class, new Dispatcher($application));
    Container::setInstance($application);
    Facade::setFacadeApplication($application);
    (new AiServiceProvider($application))->register();
    AnonymousAgent::fake(['Transport response']);

    $response = (new LaravelAiTransport)->generate(
        new AiRequest('Generate a response.'),
        [new ProviderRoute('openai', 'test-model')],
    );

    expect($response->text())->toBe('Transport response')
        ->and($response->provider)->toBe('openai')
        ->and($response->model)->toBe('test-model')
        ->and($response->metadata)->toHaveKey('invocation_id');

    AnonymousAgent::assertPrompted('Generate a response.');
});
