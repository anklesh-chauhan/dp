<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaService implements LLMServiceInterface
{
    public function generateStructured(string $prompt, array $jsonSchema): ?array
    {
        $response = Http::withOptions([
            'timeout' => (int) config('services.ollama.timeout', 600),
            'curl' => [CURLOPT_TIMEOUT => (int) config('services.ollama.timeout', 600)],
        ])->post(config('services.ollama.url').'/api/generate', [
            'model' => config('services.ollama.model', 'qwen2.5:7b'),
            'prompt' => $prompt,
            'stream' => false,
            'format' => $jsonSchema,
            'options' => [
                'temperature' => 0.1,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Ollama structured request failed (HTTP '.$response->status().'): '.$response->body());
        }

        $rawJson = $response->json('response');

        if (blank($rawJson)) {
            throw new RuntimeException('Ollama returned an empty structured response.');
        }

        $decoded = json_decode($rawJson, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Ollama returned invalid JSON for structured output.');
        }

        return $decoded;
    }
}
