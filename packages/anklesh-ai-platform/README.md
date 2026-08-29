# Anklesh AI Platform

An application-level AI orchestration package for Laravel. It builds on the official Laravel AI SDK and adds:

- use-case based provider routes;
- data-classification governance;
- ordered provider and model failover;
- normalized text and structured responses;
- tools, messages, attachments, and dynamic JSON schemas;
- deterministic post-generation output rules;
- execution lifecycle events without recording prompt content;
- replaceable transport and observability contracts.

The package supports PHP 8.3+, Laravel 12 or 13, and Laravel AI 0.10.

## Installation

Install the package through Composer:

```bash
composer require anklesh/ai-platform
```

For local development before the package is published, add a Composer path repository in the consuming project:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../docupharma/packages/anklesh-ai-platform"
        }
    ]
}
```

Then install the development version:

```bash
composer require anklesh/ai-platform:@dev
```

Laravel discovers the service provider automatically. Publish the routing configuration when customization is needed:

```bash
php artisan vendor:publish --tag=anklesh-ai-platform-config
```

Configure provider credentials and models in Laravel AI's `config/ai.php`. The platform configuration only controls governance and routing; it does not duplicate provider credentials.

## Basic usage

Inject `AiPlatform` and submit an `AiRequest`:

```php
use Anklesh\AiPlatform\AiPlatform;
use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Enums\DataClassification;

final readonly class SummarizeArticle
{
    public function __construct(private AiPlatform $ai) {}

    public function handle(string $article): string
    {
        return $this->ai->prompt(new AiRequest(
            prompt: $article,
            useCase: 'article_summarization',
            classification: DataClassification::Internal,
            instructions: 'Summarize the article in five concise bullet points.',
        ))->text();
    }
}
```

The facade is also available without a global alias:

```php
use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Facades\AnkleshAi;

$response = AnkleshAi::prompt(new AiRequest(
    prompt: 'Explain dependency injection.',
));
```

## Routing and governance

Define an ordered failover chain for each application use case:

```php
// config/anklesh-ai-platform.php
return [
    'routes' => [
        'default' => [
            ['provider' => 'openai', 'model' => 'gpt-4.1-mini'],
            ['provider' => 'gemini', 'model' => 'gemini-2.5-flash'],
        ],
        'private_analysis' => [
            ['provider' => 'ollama', 'model' => 'qwen2.5:14b'],
        ],
    ],
    'providers' => [
        'openai' => [
            'enabled' => true,
            'classifications' => ['public', 'internal'],
        ],
        'gemini' => [
            'enabled' => true,
            'classifications' => ['public', 'internal'],
        ],
        'ollama' => [
            'enabled' => true,
            'classifications' => ['*'],
        ],
    ],
];
```

Providers not listed under `providers` are enabled for every classification. Set `enabled` to `false` to disable a configured provider without changing every route.

Laravel AI performs failover only for provider failures that are safe to retry, such as rate limits or provider outages. Validation and bad-request failures are not silently sent to another provider.

## Structured output

Pass a Laravel JSON schema closure in the request:

```php
use Anklesh\AiPlatform\Data\AiRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;

$response = $ai->prompt(new AiRequest(
    prompt: 'Review this customer message.',
    useCase: 'sentiment_analysis',
    schema: fn (JsonSchema $schema): array => [
        'sentiment' => $schema->string()->enum(['positive', 'neutral', 'negative'])->required(),
        'confidence' => $schema->number()->min(0)->max(1)->required(),
    ],
));

$result = $response->structured();
```

Provider schemas control the generated shape. Deterministic rules can then enforce application invariants:

```php
use Anklesh\AiPlatform\Validation\Rules\RequiredPathRule;

$response = $ai->prompt(new AiRequest(
    prompt: 'Extract the approved customer details.',
    schema: fn (JsonSchema $schema): array => [
        'customer' => $schema->object(fn (JsonSchema $schema): array => [
            'name' => $schema->string()->required(),
        ])->required(),
    ],
    rules: [
        new RequiredPathRule('customer.name'),
    ],
));
```

Failed error-level rules throw `OutputValidationException` with a `ValidationResult`. Warning and informational issues do not fail the request.

## Tools, history, and attachments

`AiRequest` accepts the same tools, messages, and attachments used by Laravel AI anonymous agents:

```php
$response = $ai->prompt(new AiRequest(
    prompt: 'Answer using the supplied sources.',
    messages: $messages,
    tools: $tools,
    attachments: $attachments,
));
```

## Observability

Every request dispatches one of these lifecycle events:

- `AiExecutionStarted`
- `AiExecutionSucceeded`
- `AiExecutionFailed`

Events contain an execution ULID, use case, classification, eligible routes, duration, provider metadata, and token usage. Prompt and response content are not added to the execution context.

Exception messages are hidden by default because provider errors can contain sensitive context. Set `ANKLESH_AI_INCLUDE_ERROR_MESSAGES=true` only when the destination telemetry system is approved to receive them. Observability listener failures never change the result of the AI request.

Listen to these events to persist metrics in the host project's preferred database or telemetry system.

## Replacing integrations

Applications can replace either boundary in their own service provider:

```php
use Anklesh\AiPlatform\Contracts\AiTransport;
use Anklesh\AiPlatform\Contracts\ExecutionRecorder;

$this->app->bind(AiTransport::class, CustomTransport::class);
$this->app->bind(ExecutionRecorder::class, DatabaseExecutionRecorder::class);
```

This allows projects to retain governance and routing while using a different transport or persistence strategy.

## Testing consumers

Bind a fake `AiTransport` in the consuming application's test container. This prevents network calls and lets the test assert the routed providers and normalized response independently from Laravel AI provider fakes.
