<?php

declare(strict_types=1);

return [

    'providers' => [

        'gemini' => [
            'enabled' => env('GEMINI_ENABLED', true),
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env(
                'GEMINI_MODEL',
                'gemini-2.5-flash',
            ),
            'timeout' => env('GEMINI_TIMEOUT', 120),
        ],

        'openai' => [
            'enabled' => env('OPENAI_ENABLED', false),
            'api_key' => env('OPENAI_API_KEY'),
            'url' => env(
                'OPENAI_URL',
                'https://api.openai.com/v1',
            ),
            'model' => env(
                'OPENAI_MODEL',
                'gpt-4.1-mini',
            ),
            'timeout' => env('OPENAI_TIMEOUT', 120),
        ],

        'ollama' => [
            'enabled' => env('OLLAMA_ENABLED', true),
            'url' => env(
                'OLLAMA_URL',
                'http://localhost:11434',
            ),
            'model' => env(
                'OLLAMA_MODEL',
                'qwen2.5:3b',
            ),
            'timeout' => env('OLLAMA_TIMEOUT', 600),
        ],

    ],

    'routing' => [

        'document_classification' => [
            'openai',
            'gemini',
            'ollama',
        ],

        'document_type_selection' => [
            'openai',
            'gemini',
            'ollama',
        ],

        'regulation_tagging' => [
            'openai',
            'gemini',
            'ollama',
        ],

        'sop_generation' => [
            'gemini',
            'openai',
            'ollama',
        ],

        'document_description_generation' => [
            'openai',
            'gemini',
            'ollama',
        ],

        'regulated_template_generation' => [
            'openai',
            'gemini',
            'ollama',
        ],

        'template_section_generation' => [
            'openai',
            'gemini',
            'ollama',
        ],

        'template_section_completion' => [
            'openai',
            'gemini',
            'ollama',
        ],

    ],

];
