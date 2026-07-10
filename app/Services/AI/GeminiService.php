<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use App\Services\AI\LLMServiceInterface;

class GeminiService implements LLMServiceInterface
{
    public function generateStructured(string $prompt, array $jsonSchema): ?array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . config('services.gemini.key'), [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $jsonSchema,
                'temperature' => 0.1
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception('Gemini structured request failed: ' . $response->status());
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        return $text ? json_decode($text, true) : null;
    }
}
