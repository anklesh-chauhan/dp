<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FallbackLLMService implements LLMServiceInterface
{
    public function __construct(
        protected GeminiService $geminiService,
        protected OllamaService $ollamaService
    ) {}

    public function generateStructured(string $prompt, array $jsonSchema): ?array
    {
        try {
            $result = $this->geminiService->generateStructured($prompt, $jsonSchema);

            if ($result !== null) {
                Log::info('Structured LLM generation succeeded via Gemini.');

                return $result;
            }

            Log::warning('Gemini structured generation returned null, trying Ollama.');
        } catch (Throwable $e) {
            Log::warning('Gemini structured generation failed, trying Ollama: '.$e->getMessage());
        }

        try {
            $result = $this->ollamaService->generateStructured($prompt, $jsonSchema);

            if ($result !== null) {
                Log::info('Structured LLM generation succeeded via Ollama fallback.');

                return $result;
            }

            throw new RuntimeException('Ollama structured generation returned null.');
        } catch (Throwable $e) {
            throw new RuntimeException('All LLM providers failed. Last error: '.$e->getMessage(), 0, $e);
        }
    }
}
