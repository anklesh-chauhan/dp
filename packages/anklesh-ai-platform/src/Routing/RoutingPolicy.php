<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Routing;

use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Data\ProviderRoute;
use Anklesh\AiPlatform\Exceptions\InvalidRoutingConfiguration;
use Anklesh\AiPlatform\Exceptions\NoEligibleProvider;
use Illuminate\Contracts\Config\Repository;

final readonly class RoutingPolicy
{
    public function __construct(private Repository $config) {}

    /** @return list<ProviderRoute> */
    public function providersFor(AiRequest $request): array
    {
        $configuration = $this->config->get('anklesh-ai-platform', []);

        if (! is_array($configuration)) {
            throw new InvalidRoutingConfiguration('The anklesh-ai-platform configuration must be an array.');
        }

        $routes = $configuration['routes'] ?? [];
        $providerPolicies = $configuration['providers'] ?? [];

        if (! is_array($routes) || ! is_array($providerPolicies)) {
            throw new InvalidRoutingConfiguration('AI routes and provider governance must be configured as arrays.');
        }

        $configuredRoutes = $routes[$request->useCase] ?? $routes['default'] ?? [];

        if (! is_array($configuredRoutes)) {
            throw new InvalidRoutingConfiguration(sprintf(
                'The AI route for use case [%s] must be an array.',
                $request->useCase,
            ));
        }

        $eligibleRoutes = [];

        foreach ($configuredRoutes as $configuredRoute) {
            $route = ProviderRoute::fromConfig($configuredRoute);

            if ($this->providerIsEligible($route, $request, $providerPolicies)) {
                $eligibleRoutes[] = $route;
            }
        }

        if ($eligibleRoutes === []) {
            throw NoEligibleProvider::for($request->useCase, $request->classificationValue());
        }

        return $eligibleRoutes;
    }

    /** @param array<string, mixed> $providerPolicies */
    private function providerIsEligible(
        ProviderRoute $route,
        AiRequest $request,
        array $providerPolicies,
    ): bool {
        $policy = $providerPolicies[$route->provider] ?? null;

        if ($policy === null) {
            return true;
        }

        if (! is_array($policy)) {
            throw new InvalidRoutingConfiguration(sprintf(
                'The governance policy for provider [%s] must be an array.',
                $route->provider,
            ));
        }

        if (($policy['enabled'] ?? true) !== true) {
            return false;
        }

        $classifications = $policy['classifications'] ?? ['*'];

        if (! is_array($classifications)) {
            throw new InvalidRoutingConfiguration(sprintf(
                'The classifications for provider [%s] must be an array.',
                $route->provider,
            ));
        }

        return in_array('*', $classifications, true)
            || in_array($request->classificationValue(), $classifications, true);
    }
}
