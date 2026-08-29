<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;

final class AiProviderGovernance
{
    /** @return list<string> */
    public function providersFor(
        AIUseCase $useCase,
        AIDataClassification $classification,
    ): array {
        $routedProviders = config("ai.routing.{$useCase->value}", []);
        $approvedProviders = config("ai.governance.classification_providers.{$classification->value}", []);

        if (! is_array($routedProviders) || ! is_array($approvedProviders)) {
            return [];
        }

        return array_values(array_filter(
            array_intersect($routedProviders, $approvedProviders),
            fn (mixed $provider): bool => is_string($provider)
                && (bool) config("ai.providers.{$provider}.enabled", false),
        ));
    }
}
