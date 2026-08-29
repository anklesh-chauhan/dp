<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Data;

use Anklesh\AiPlatform\Exceptions\InvalidRoutingConfiguration;

final readonly class ProviderRoute
{
    public function __construct(
        public string $provider,
        public ?string $model = null,
    ) {
        if (trim($this->provider) === '') {
            throw new InvalidRoutingConfiguration('An AI provider route must have a non-empty provider name.');
        }
    }

    public static function fromConfig(mixed $route): self
    {
        if (is_string($route)) {
            return new self($route);
        }

        if (! is_array($route) || ! is_string($route['provider'] ?? null)) {
            throw new InvalidRoutingConfiguration('Every AI route must be a provider string or an array containing a provider key.');
        }

        $model = $route['model'] ?? null;

        if ($model !== null && ! is_string($model)) {
            throw new InvalidRoutingConfiguration('An AI route model must be a string or null.');
        }

        return new self($route['provider'], $model);
    }
}
