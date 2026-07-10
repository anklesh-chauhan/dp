<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService implements LLMServiceInterface
{
    public function generateStructured(string $prompt, array $jsonSchema): ?array
    {
        $apiKey = config('services.gemini.key');

        if (blank($apiKey)) {
            throw new RuntimeException('Gemini API key is not configured (GEMINI_API_KEY).');
        }

        $model = config('services.gemini.model', 'gemini-3.5-flash');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout((int) config('services.gemini.timeout', 180))->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $jsonSchema,
                    'temperature' => 0.1,
                ],
            ]
        );

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();

            throw new RuntimeException("Gemini structured request failed (HTTP {$response->status()}): {$message}");
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (blank($text)) {
            throw new RuntimeException('Gemini returned an empty structured response.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON for structured output.');
        }

        return $decoded;
    }
}
