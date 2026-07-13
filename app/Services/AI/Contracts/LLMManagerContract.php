<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;

interface LLMManagerContract
{
    public function generate(LLMRequest $request): LLMResponse;
}
