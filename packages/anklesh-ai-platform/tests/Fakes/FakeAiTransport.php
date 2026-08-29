<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Tests\Fakes;

use Anklesh\AiPlatform\Contracts\AiTransport;
use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ProviderRoute;
use RuntimeException;

final class FakeAiTransport implements AiTransport
{
    public ?AiRequest $request = null;

    /** @var list<ProviderRoute> */
    public array $routes = [];

    public function __construct(
        public ?AiResponse $response = null,
        public ?RuntimeException $exception = null,
    ) {}

    public function generate(AiRequest $request, array $routes): AiResponse
    {
        $this->request = $request;
        $this->routes = $routes;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response ?? new AiResponse('Fake response', 'fake');
    }
}
