<?php

declare(strict_types=1);

use Anklesh\AiPlatform\AiPlatform;
use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Exceptions\OutputValidationException;
use Anklesh\AiPlatform\Routing\RoutingPolicy;
use Anklesh\AiPlatform\Tests\Fakes\FakeAiTransport;
use Anklesh\AiPlatform\Tests\Fakes\RecordingExecutionRecorder;
use Anklesh\AiPlatform\Tests\Fakes\ThrowingExecutionRecorder;
use Anklesh\AiPlatform\Validation\OutputValidator;
use Anklesh\AiPlatform\Validation\Rules\RequiredPathRule;
use Illuminate\Config\Repository;

it('routes, generates, and records a successful execution', function () {
    $expectedResponse = new AiResponse(
        content: 'Generated text',
        provider: 'gemini',
        model: 'gemini-fast',
        inputTokens: 12,
        outputTokens: 5,
    );
    $transport = new FakeAiTransport(response: $expectedResponse);
    $recorder = new RecordingExecutionRecorder;
    $platform = new AiPlatform(
        routing: new RoutingPolicy(new Repository([
            'anklesh-ai-platform' => [
                'routes' => ['default' => ['gemini', 'openai']],
                'providers' => [],
            ],
        ])),
        transport: $transport,
        recorder: $recorder,
        validator: new OutputValidator,
    );
    $request = new AiRequest(
        prompt: 'Generate content.',
        metadata: ['tenant_id' => 42],
    );

    $response = $platform->prompt($request);

    expect($response)->toBe($expectedResponse)
        ->and($transport->request)->toBe($request)
        ->and($transport->routes)->toHaveCount(2)
        ->and($recorder->startedContext)->not->toBeNull()
        ->and($recorder->succeededContext)->toBe($recorder->startedContext)
        ->and($recorder->failedContext)->toBeNull()
        ->and($recorder->startedContext?->metadata)->toBe(['tenant_id' => 42])
        ->and($recorder->response)->toBe($expectedResponse);
});

it('records a failed transport execution and rethrows the exception', function () {
    $exception = new RuntimeException('Provider unavailable.');
    $transport = new FakeAiTransport(exception: $exception);
    $recorder = new RecordingExecutionRecorder;
    $platform = new AiPlatform(
        routing: new RoutingPolicy(new Repository([
            'anklesh-ai-platform' => [
                'routes' => ['default' => ['openai']],
                'providers' => [],
            ],
        ])),
        transport: $transport,
        recorder: $recorder,
        validator: new OutputValidator,
    );

    try {
        $platform->prompt(new AiRequest('Generate content.'));
    } catch (RuntimeException $caught) {
        expect($caught)->toBe($exception);
    }

    expect($recorder->startedContext)->not->toBeNull()
        ->and($recorder->failedContext)->toBe($recorder->startedContext)
        ->and($recorder->succeededContext)->toBeNull()
        ->and($recorder->exception)->toBe($exception);
});

it('does not let observability failures change a successful response', function () {
    $expectedResponse = new AiResponse('Generated text', 'openai');
    $platform = new AiPlatform(
        routing: new RoutingPolicy(new Repository([
            'anklesh-ai-platform' => [
                'routes' => ['default' => ['openai']],
                'providers' => [],
            ],
        ])),
        transport: new FakeAiTransport(response: $expectedResponse),
        recorder: new ThrowingExecutionRecorder,
        validator: new OutputValidator,
    );

    expect($platform->prompt(new AiRequest('Generate content.')))->toBe($expectedResponse);
});

it('records deterministic output validation failures', function () {
    $recorder = new RecordingExecutionRecorder;
    $platform = new AiPlatform(
        routing: new RoutingPolicy(new Repository([
            'anklesh-ai-platform' => [
                'routes' => ['default' => ['openai']],
                'providers' => [],
            ],
        ])),
        transport: new FakeAiTransport(response: new AiResponse(
            ['customer' => ['name' => '']],
            'openai',
        )),
        recorder: $recorder,
        validator: new OutputValidator,
    );

    expect(fn () => $platform->prompt(new AiRequest(
        prompt: 'Extract the customer.',
        rules: [new RequiredPathRule('customer.name')],
    )))->toThrow(OutputValidationException::class);

    expect($recorder->failedContext)->toBe($recorder->startedContext)
        ->and($recorder->exception)->toBeInstanceOf(OutputValidationException::class);
});
