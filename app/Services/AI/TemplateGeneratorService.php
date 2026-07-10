<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemplateGeneratorService
{
    public function generateRegulatedTemplate(array $formData, string $regulationTags): ?array
    {

        $prompt = 'You are an expert pharmaceutical and clinical QMS Auditor. '.
            "Generate a comprehensive corporate QMS template boilerplate based on these user definitions:\n".
            "- Template Name: {$formData['name']}\n".
            "- Description: {$formData['description']}\n".
            "CRITICAL COMPLIANCE REQUIREMENT:\n".
            "The structure, sections, and variable parameters MUST strictly comply with these regulatory frameworks: {$regulationTags}.\n\n".
            'For each variable extracted, you MUST pick the most accurate data type from your allowed type schema options.';

        $jsonSchema = [
            'type' => 'object',
            'properties' => [
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'content' => ['type' => 'string'],
                            'section_order' => ['type' => 'integer'],
                            'section_type' => ['type' => 'string', 'enum' => ['rich_text', 'markdown', 'text']],
                        ],
                        'required' => ['title', 'content', 'section_order', 'section_type'],
                    ],
                ],
                'variables' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'label' => ['type' => 'string'],
                            // Injecting your exact database lookup string keys right here:
                            'datatype' => [
                                'type' => 'string',
                                'enum' => [
                                    'text', 'long_text', 'rich_text', 'integer', 'decimal',
                                    'currency', 'percentage', 'date', 'datetime', 'time',
                                    'boolean', 'checkbox', 'select', 'multi_select', 'radio',
                                    'user', 'employee', 'department', 'designation',
                                    'sop_reference', 'sop_document', 'document_number',
                                    'file', 'image', 'url', 'email', 'phone',
                                ],
                            ],
                            'default_value' => ['type' => 'string'],
                            'required' => ['type' => 'boolean'],
                        ],
                        'required' => ['name', 'label', 'datatype', 'default_value', 'required'],
                    ],
                ],
            ],
            'required' => ['sections', 'variables'],
        ];

        try {
            $response = Http::withOptions([
                'timeout' => 300,
                'curl' => [
                    CURLOPT_TIMEOUT => 300,
                ],
            ])->post(config('services.ollama.url').'/api/generate', [
                'model' => config('services.ollama.model', 'qwen2.5:7b'),
                'prompt' => $prompt,
                'stream' => false,
                'format' => $jsonSchema,
                'options' => [
                    'temperature' => 0.1,
                ],
            ]);

            if ($response->successful()) {
                return json_decode($response->json('response'), true);
            }
        } catch (\Exception $e) {
            Log::error('Regulated Template Generation Failed: '.$e->getMessage());
        }

        return null;
    }
}
