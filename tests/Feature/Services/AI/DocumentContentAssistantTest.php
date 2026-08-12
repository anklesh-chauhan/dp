<?php

declare(strict_types=1);

use App\Exceptions\ModuleNotEnabledException;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\DocumentContentAssistant;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\ContentAssistFormat;
use App\Services\AI\Enums\ContentAssistOperation;
use App\Services\AI\Enums\LLMCapability;
use App\Support\Modules\ModuleManager;
use Mockery\MockInterface;

it('creates html document content through the llm manager', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $llmManager = Mockery::mock(
        LLMManagerContract::class,
        function (MockInterface $mock): void {
            $mock
                ->shouldReceive('generate')
                ->once()
                ->withArgs(function (LLMRequest $request): bool {
                    expect($request->useCase)
                        ->toBe(AIUseCase::DOCUMENT_CONTENT_ASSISTANCE)
                        ->and($request->capability)
                        ->toBe(LLMCapability::STRUCTURED_OUTPUT)
                        ->and($request->dataClassification)
                        ->toBe(AIDataClassification::INTERNAL)
                        ->and($request->metadata)
                        ->toMatchArray([
                            'feature' => 'document_content_assistance',
                            'format' => 'html',
                            'operation' => 'create',
                        ])
                        ->and($request->prompt)
                        ->toContain('Purpose')
                        ->toContain('rich-editor-compatible HTML');

                    return true;
                })
                ->andReturn(new LLMResponse(
                    content: ['text' => '  <p>Defines cleaning responsibilities.</p>  '],
                    provider: 'gemini',
                    model: 'gemini-test',
                ));
        },
    );

    $assistant = new DocumentContentAssistant(
        llmManager: $llmManager,
        moduleManager: app(ModuleManager::class),
    );

    expect($assistant->transform(
        format: ContentAssistFormat::Html,
        operation: ContentAssistOperation::Create,
        content: '',
        context: [
            'field_label' => 'Purpose',
            'subject' => 'SOP-001',
        ],
    ))->toBe('<p>Defines cleaning responsibilities.</p>');
});

it('strips markup from plain text results', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $llmManager = Mockery::mock(
        LLMManagerContract::class,
        function (MockInterface $mock): void {
            $mock
                ->shouldReceive('generate')
                ->once()
                ->andReturn(new LLMResponse(
                    content: ['text' => '<p>Plain purpose text</p>'],
                    provider: 'ollama',
                    model: 'test',
                ));
        },
    );

    $assistant = new DocumentContentAssistant(
        llmManager: $llmManager,
        moduleManager: app(ModuleManager::class),
    );

    expect($assistant->transform(
        format: ContentAssistFormat::Plain,
        operation: ContentAssistOperation::Polish,
        content: 'purpose draft',
    ))->toBe('Plain purpose text');
});

it('requires existing text before polish or shorten', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $llmManager = Mockery::mock(LLMManagerContract::class);
    $llmManager->shouldNotReceive('generate');

    $assistant = new DocumentContentAssistant(
        llmManager: $llmManager,
        moduleManager: app(ModuleManager::class),
    );

    expect($assistant->transform(
        format: ContentAssistFormat::Plain,
        operation: ContentAssistOperation::Shorten,
        content: '  ',
    ))->toBeNull();
});

it('blocks assistance when the ai module is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    $assistant = new DocumentContentAssistant(
        llmManager: Mockery::mock(LLMManagerContract::class),
        moduleManager: app(ModuleManager::class),
    );

    expect(fn () => $assistant->transform(
        format: ContentAssistFormat::Html,
        operation: ContentAssistOperation::Create,
        content: '',
    ))->toThrow(ModuleNotEnabledException::class);
});
