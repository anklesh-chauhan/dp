<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use App\Services\AI\LLMServiceInterface;

class OllamaService implements LLMServiceInterface
{
    public function generateStructured(string $prompt, array $jsonSchema): ?array
    {
        $response = Http::withOptions([
            'timeout' => 300,
            'curl' => [CURLOPT_TIMEOUT => 300],
        ])->post(config('services.ollama.url') . '/api/generate', [
            'model' => config('services.ollama.model', 'qwen2.5:7b'),
            'prompt' => $prompt,
            'stream' => false,
            'format' => $jsonSchema,
            'options' => [
                'temperature' => 0.1,
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Ollama structured request failed: ' . $response->status());
        }

        $rawJson = $response->json('response');
        return $rawJson ? json_decode($rawJson, true) : null;
    }
}
