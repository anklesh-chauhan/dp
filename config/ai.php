<?php

declare(strict_types=1);

return [

    'default' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    'default_for_images' => 'gemini',

    'default_for_audio' => 'openai',

    'default_for_transcription' => 'openai',

    'default_for_embeddings' => 'openai',

    'default_for_reranking' => 'cohere',

    'conversations' => [
        'generate_title' => false,
        'connection' => env('AI_CONVERSATION_DB_CONNECTION'),
        'tables' => [
            'conversations' => 'agent_conversations',
            'messages' => 'agent_conversation_messages',
        ],
    ],

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    'governance' => [
        'classification_providers' => [
            'public' => array_values(array_filter(array_map(
                static fn (string $provider): string => trim($provider),
                explode(',', (string) env('AI_PUBLIC_PROVIDERS', 'gemini,openai,ollama')),
            ))),
            'internal' => array_values(array_filter(array_map(
                static fn (string $provider): string => trim($provider),
                explode(',', (string) env('AI_INTERNAL_PROVIDERS', 'ollama')),
            ))),
            'confidential' => array_values(array_filter(array_map(
                static fn (string $provider): string => trim($provider),
                explode(',', (string) env('AI_CONFIDENTIAL_PROVIDERS', 'ollama')),
            ))),
            'restricted' => array_values(array_filter(array_map(
                static fn (string $provider): string => trim($provider),
                explode(',', (string) env('AI_RESTRICTED_PROVIDERS', 'ollama')),
            ))),
        ],
        'rate_limits' => [
            'controlled_document_drafting' => (int) env('AI_DRAFT_REQUESTS_PER_MINUTE', 10),
            'application_assistant' => (int) env('AI_ASSISTANT_REQUESTS_PER_MINUTE', 12),
        ],
    ],

    'draft_queue' => [
        'connection' => env('AI_DRAFT_QUEUE_CONNECTION'),
        'queue' => env('AI_DRAFT_QUEUE', 'default'),
        'timeout' => (int) env('AI_DRAFT_QUEUE_TIMEOUT', 900),
    ],

    'providers' => [

        'gemini' => [
            'driver' => 'gemini',
            'enabled' => env('GEMINI_ENABLED', true),
            'key' => env('GEMINI_API_KEY'),
            'api_key' => env('GEMINI_API_KEY'),
            'url' => env(
                'GEMINI_URL',
                'https://generativelanguage.googleapis.com/v1beta/',
            ),
            'model' => env(
                'GEMINI_MODEL',
                'gemini-2.5-flash',
            ),
            'models' => [
                'text' => [
                    'default' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
                ],
            ],
            'timeout' => env('GEMINI_TIMEOUT', 120),
        ],

        'openai' => [
            'driver' => 'openai',
            'enabled' => env('OPENAI_ENABLED', false),
            'key' => env('OPENAI_API_KEY'),
            'api_key' => env('OPENAI_API_KEY'),
            'url' => env(
                'OPENAI_URL',
                'https://api.openai.com/v1',
            ),
            'store' => env('OPENAI_STORE', false),
            'model' => env(
                'OPENAI_MODEL',
                'gpt-4.1-mini',
            ),
            'models' => [
                'text' => [
                    'default' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
                ],
            ],
            'timeout' => env('OPENAI_TIMEOUT', 120),
        ],

        'ollama' => [
            'driver' => 'ollama',
            'enabled' => env('OLLAMA_ENABLED', true),
            'key' => env('OLLAMA_API_KEY', ''),
            'url' => env(
                'OLLAMA_URL',
                'http://localhost:11434',
            ),
            'model' => env(
                'OLLAMA_MODEL',
                'qwen2.5:3b',
            ),
            'models' => [
                'text' => [
                    'default' => env('OLLAMA_MODEL', 'qwen2.5:3b'),
                ],
            ],
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

        'controlled_document_drafting' => [
            'gemini',
            'openai',
            'ollama',
        ],

        'application_assistant' => [
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

        'approval_submission_note' => [
            'openai',
            'gemini',
            'ollama',
        ],

        'approval_decision_rationale' => [
            'openai',
            'gemini',
            'ollama',
        ],

        'document_content_assistance' => [
            'openai',
            'gemini',
            'ollama',
        ],

    ],

];
