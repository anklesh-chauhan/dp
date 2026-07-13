<?php

declare(strict_types=1);

use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\DocumentAiDescriptionGenerator;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use Mockery\MockInterface;

uses(Tests\TestCase::class);

it('generates and normalizes a document description', function (): void {
    $llmManager = Mockery::mock(
        LLMManagerContract::class,
        function (MockInterface $mock): void {
            $mock
                ->shouldReceive('generate')
                ->once()
                ->withArgs(function (LLMRequest $request): bool {
                    expect($request->useCase)
                        ->toBe(AIUseCase::DOCUMENT_DESCRIPTION_GENERATION)
                        ->and($request->capability)
                        ->toBe(LLMCapability::STRUCTURED_OUTPUT)
                        ->and($request->dataClassification)
                        ->toBe(AIDataClassification::INTERNAL)
                        ->and($request->temperature)
                        ->toBe(0.2)
                        ->and($request->metadata)
                        ->toBe([
                            'feature' => 'document_description_generation',
                        ])
                        ->and($request->jsonSchema)
                        ->toBeArray()
                        ->and($request->jsonSchema['required'])
                        ->toBe([
                            'description',
                            'reasoning',
                        ])
                        ->and($request->prompt)
                        ->toContain('Deviation Management Procedure')
                        ->toContain('Quality Assurance');

                    return true;
                })
                ->andReturn(
                    new LLMResponse(
                        content: [
                            'description' => '  Defines the requirements for managing deviations.  ',
                            'reasoning' => '  Suitable for the Quality Assurance department.  ',
                        ],
                        provider: 'gemini',
                        model: 'gemini-test-model',
                    ),
                );
        },
    );

    $generator = new DocumentAiDescriptionGenerator(
        llmManager: $llmManager,
    );

    $result = $generator->generate(
        name: 'Deviation Management Procedure',
        departmentName: 'Quality Assurance',
    );

    expect($result)
        ->toBe([
            'description' => 'Defines the requirements for managing deviations.',
            'reasoning' => 'Suitable for the Quality Assurance department.',
        ]);
});

it('returns an empty result without calling the llm manager when the document name is blank', function (): void {
    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldNotReceive('generate');

    $generator = new DocumentAiDescriptionGenerator(
        llmManager: $llmManager,
    );

    $result = $generator->generate(
        name: '   ',
        departmentName: 'Quality Assurance',
    );

    expect($result)
        ->toBe([
            'description' => null,
            'reasoning' => null,
        ]);
});

it('normalizes invalid and blank response fields to null', function (): void {
    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->andReturn(
            new LLMResponse(
                content: [
                    'description' => ['invalid'],
                    'reasoning' => '   ',
                ],
                provider: 'ollama',
                model: 'test-model',
            ),
        );

    $generator = new DocumentAiDescriptionGenerator(
        llmManager: $llmManager,
    );

    $result = $generator->generate(
        name: 'Deviation Management Procedure',
        departmentName: 'Quality Assurance',
    );

    expect($result)
        ->toBe([
            'description' => null,
            'reasoning' => null,
        ]);
});

it('returns an empty result when the llm manager throws an exception', function (): void {
    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->andThrow(
            new RuntimeException('All eligible LLM providers failed.'),
        );

    $generator = new DocumentAiDescriptionGenerator(
        llmManager: $llmManager,
    );

    $result = $generator->generate(
        name: 'Deviation Management Procedure',
        departmentName: 'Quality Assurance',
    );

    expect($result)
        ->toBe([
            'description' => null,
            'reasoning' => null,
        ]);
});

it('returns an empty result when the llm response is not structured', function (): void {
    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->andReturn(
            new LLMResponse(
                content: 'This is plain text instead of structured content.',
                provider: 'ollama',
                model: 'test-model',
            ),
        );

    $generator = new DocumentAiDescriptionGenerator(
        llmManager: $llmManager,
    );

    $result = $generator->generate(
        name: 'Deviation Management Procedure',
        departmentName: 'Quality Assurance',
    );

    expect($result)
        ->toBe([
            'description' => null,
            'reasoning' => null,
        ]);
});
