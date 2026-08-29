<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Contracts;

use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ProviderRoute;

interface AiTransport
{
    /** @param list<ProviderRoute> $routes */
    public function generate(AiRequest $request, array $routes): AiResponse;
}
