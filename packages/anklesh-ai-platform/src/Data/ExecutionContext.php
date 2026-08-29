<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Data;

final readonly class ExecutionContext
{
    /**
     * @param  list<ProviderRoute>  $routes
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $useCase,
        public string $classification,
        public array $routes,
        public array $metadata = [],
    ) {}
}
