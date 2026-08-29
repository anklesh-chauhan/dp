<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Provider Routes
    |--------------------------------------------------------------------------
    |
    | Each use case may define an ordered provider failover chain. Provider
    | credentials and driver details remain in Laravel AI's config/ai.php.
    |
    */
    'routes' => [
        'default' => [
            [
                'provider' => env('ANKLESH_AI_DEFAULT_PROVIDER', 'openai'),
                'model' => env('ANKLESH_AI_DEFAULT_MODEL'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Governance
    |--------------------------------------------------------------------------
    |
    | Providers omitted here are enabled for every classification. Add an
    | entry when a provider must be disabled or restricted by classification.
    |
    */
    'providers' => [
        // 'openai' => [
        //     'enabled' => true,
        //     'classifications' => ['public', 'internal'],
        // ],
    ],

    'observability' => [
        'include_error_messages' => env('ANKLESH_AI_INCLUDE_ERROR_MESSAGES', false),
    ],
];
