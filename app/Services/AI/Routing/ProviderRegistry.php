<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\Services\AI\Contracts\LLMProvider;
use InvalidArgumentException;

final class ProviderRegistry
{
    /**
     * @var array<string, LLMProvider>
     */
    private array $providers = [];

    public function register(LLMProvider $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->providers);
    }

    public function get(string $name): LLMProvider
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException(
                sprintf(
                    'LLM provider [%s] is not registered.',
                    $name,
                ),
            );
        }

        return $this->providers[$name];
    }

    /**
     * @return array<string, LLMProvider>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
