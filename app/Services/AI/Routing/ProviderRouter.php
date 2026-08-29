<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\Services\AI\Contracts\LLMProvider;
use App\Services\AI\Data\LLMRequest;

final readonly class ProviderRouter
{
    public function __construct(
        private ProviderRegistry $registry,
        private AiProviderGovernance $governance,
    ) {}

    /**
     * @return array<int, LLMProvider>
     */
    public function providersFor(LLMRequest $request): array
    {
        $providerNames = $this->governance->providersFor(
            $request->useCase,
            $request->dataClassification,
        );

        $providers = [];

        foreach ($providerNames as $providerName) {
            if (! $this->registry->has($providerName)) {
                continue;
            }

            $provider = $this->registry->get($providerName);

            if (! $provider->supports($request->capability)) {
                continue;
            }

            $providers[] = $provider;
        }

        return $providers;
    }
}
