<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Http;

class FallbackLLMService implements LLMServiceInterface
{
    public function __construct(
        protected GeminiService $geminiService,
        protected OllamaService $ollamaService
    ) {}

    public function generateStructured(string $prompt, array $jsonSchema): ?array
    {
        try {
            return $this->geminiService->generateStructured($prompt, $jsonSchema);
        } catch (\Exception $e) {
            Log::warning('Gemini structured generation failed, trying Ollama: ' . $e->getMessage());
            return $this->ollamaService->generateStructured($prompt, $jsonSchema);
        }
    }
}
