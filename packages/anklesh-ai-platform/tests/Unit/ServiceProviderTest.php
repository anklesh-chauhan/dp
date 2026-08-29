<?php

declare(strict_types=1);

use Anklesh\AiPlatform\AiPlatform;
use Anklesh\AiPlatform\AiPlatformServiceProvider;
use Anklesh\AiPlatform\Contracts\AiTransport;
use Anklesh\AiPlatform\Contracts\ExecutionRecorder;
use Anklesh\AiPlatform\Observability\EventExecutionRecorder;
use Anklesh\AiPlatform\Transports\LaravelAiTransport;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;

it('registers the package configuration and default integrations', function () {
    $application = new Application(dirname(__DIR__, 4));
    $configuration = new Repository;
    $application->instance('config', $configuration);
    $application->instance(RepositoryContract::class, $configuration);
    $application->instance(DispatcherContract::class, new Dispatcher($application));

    (new AiPlatformServiceProvider($application))->register();

    expect($configuration->get('anklesh-ai-platform.routes.default'))->not->toBeEmpty()
        ->and($application->make(AiTransport::class))->toBeInstanceOf(LaravelAiTransport::class)
        ->and($application->make(ExecutionRecorder::class))->toBeInstanceOf(EventExecutionRecorder::class)
        ->and($application->make(AiPlatform::class))->toBeInstanceOf(AiPlatform::class)
        ->and($application->make('anklesh-ai-platform'))->toBe($application->make(AiPlatform::class));
});
