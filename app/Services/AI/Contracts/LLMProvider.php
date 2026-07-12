<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\LLMCapability;

interface LLMProvider
{
    public function name(): string;

    public function model(): string;

    public function supports(LLMCapability $capability): bool;

    public function generate(LLMRequest $request): LLMResponse;
}
