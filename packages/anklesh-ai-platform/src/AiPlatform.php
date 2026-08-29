<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform;

use Anklesh\AiPlatform\Contracts\AiTransport;
use Anklesh\AiPlatform\Contracts\ExecutionRecorder;
use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ExecutionContext;
use Anklesh\AiPlatform\Exceptions\OutputValidationException;
use Anklesh\AiPlatform\Routing\RoutingPolicy;
use Anklesh\AiPlatform\Validation\OutputValidator;
use Closure;
use Illuminate\Support\Str;
use Throwable;

final readonly class AiPlatform
{
    public function __construct(
        private RoutingPolicy $routing,
        private AiTransport $transport,
        private ExecutionRecorder $recorder,
        private OutputValidator $validator,
    ) {}

    public function prompt(AiRequest $request): AiResponse
    {
        $routes = $this->routing->providersFor($request);
        $context = new ExecutionContext(
            id: (string) Str::ulid(),
            useCase: $request->useCase,
            classification: $request->classificationValue(),
            routes: $routes,
            metadata: $request->metadata,
        );

        $startedAt = hrtime(true);
        $this->recordSafely(fn () => $this->recorder->started($context));

        try {
            $response = $this->transport->generate($request, $routes);
            $validationResult = $this->validator->validate($response->content, $request->rules);

            if ($validationResult->failed()) {
                throw new OutputValidationException($validationResult);
            }
        } catch (Throwable $exception) {
            $this->recordSafely(fn () => $this->recorder->failed(
                $context,
                $exception,
                $this->elapsedMilliseconds($startedAt),
            ));

            throw $exception;
        }

        $this->recordSafely(fn () => $this->recorder->succeeded(
            $context,
            $response,
            $this->elapsedMilliseconds($startedAt),
        ));

        return $response;
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return max(0, (int) ((hrtime(true) - $startedAt) / 1_000_000));
    }

    private function recordSafely(Closure $callback): void
    {
        try {
            $callback();
        } catch (Throwable) {
        }
    }
}
