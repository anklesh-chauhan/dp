<?php

namespace App\Services\AI;

interface LLMServiceInterface
{
    public function generateStructured(string $prompt, array $jsonSchema): ?array;

}
