<?php

declare(strict_types=1);

use App\Models\VariableDataType;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\TemplateGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('generates a regulated template using authorized variable data types', function (): void {
    VariableDataType::query()->create([
        'code' => VariableDataType::TEXT,
        'name' => 'Text',
        'sort_order' => 10,
    ]);

    VariableDataType::query()->create([
        'code' => VariableDataType::DATE,
        'name' => 'Date',
        'sort_order' => 20,
    ]);

    VariableDataType::query()->create([
        'code' => VariableDataType::BOOLEAN,
        'name' => 'Boolean',
        'sort_order' => 30,
    ]);

    $expectedResult = [
        'sections' => [
            [
                'title' => 'Purpose',
                'content' => 'Defines the purpose of the deviation management procedure.',
                'section_order' => 1,
                'section_type' => 'rich_text',
            ],
        ],
        'variables' => [
            [
                'name' => 'deviation_date',
                'label' => 'Deviation Date',
                'datatype' => 'date',
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $llmManager = Mockery::mock(
        LLMManagerContract::class,
        function (MockInterface $mock) use ($expectedResult): void {
            $mock
                ->shouldReceive('generate')
                ->once()
                ->withArgs(function (LLMRequest $request): bool {
                    expect($request->useCase)
                        ->toBe(AIUseCase::REGULATED_TEMPLATE_GENERATION)
                        ->and($request->capability)
                        ->toBe(LLMCapability::STRUCTURED_OUTPUT)
                        ->and($request->dataClassification)
                        ->toBe(AIDataClassification::INTERNAL)
                        ->and($request->temperature)
                        ->toBe(0.1)
                        ->and($request->metadata)
                        ->toBe([
                            'feature' => 'regulated_template_generation',
                        ])
                        ->and($request->prompt)
                        ->toContain('Deviation Management Procedure')
                        ->toContain('Procedure for managing quality deviations.')
                        ->toContain('EU GMP, FDA 21 CFR Part 211');

                    $datatypeEnum = $request->jsonSchema['properties']
                        ['variables']
                        ['items']
                        ['properties']
                        ['datatype']
                        ['enum'];

                    expect($datatypeEnum)
                        ->toBe([
                            VariableDataType::BOOLEAN,
                            VariableDataType::DATE,
                            VariableDataType::TEXT,
                        ]);

                    return true;
                })
                ->andReturn(
                    new LLMResponse(
                        content: $expectedResult,
                        provider: 'gemini',
                        model: 'gemini-test-model',
                    ),
                );
        },
    );

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->generateRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP, FDA 21 CFR Part 211',
    );

    expect($result)
        ->toBe($expectedResult);
});

it('uses fallback variable data types when no authorized data types exist', function (): void {
    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->withArgs(function (LLMRequest $request): bool {
            $datatypeEnum = $request->jsonSchema['properties']
                ['variables']
                ['items']
                ['properties']
                ['datatype']
                ['enum'];

            expect($datatypeEnum)
                ->toBe([
                    'text',
                    'long_text',
                    'integer',
                    'boolean',
                ]);

            return true;
        })
        ->andReturn(
            new LLMResponse(
                content: [
                    'sections' => [],
                    'variables' => [],
                ],
                provider: 'ollama',
                model: 'test-model',
            ),
        );

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->generateRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP',
    );

    expect($result)
        ->toBe([
            'sections' => [],
            'variables' => [],
        ]);
});

it('excludes invalid variable data type codes from the response schema', function (): void {
    VariableDataType::query()->create([
        'code' => VariableDataType::TEXT,
        'name' => 'Text',
        'sort_order' => 10,
    ]);

    VariableDataType::query()->create([
        'code' => '',
        'name' => 'Invalid Empty Code',
        'sort_order' => 20,
    ]);

    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->withArgs(function (LLMRequest $request): bool {
            $datatypeEnum = $request->jsonSchema['properties']
                ['variables']
                ['items']
                ['properties']
                ['datatype']
                ['enum'];

            expect($datatypeEnum)
                ->toBe([
                    VariableDataType::TEXT,
                ]);

            return true;
        })
        ->andReturn(
            new LLMResponse(
                content: [
                    'sections' => [],
                    'variables' => [],
                ],
                provider: 'gemini',
                model: 'test-model',
            ),
        );

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->generateRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP',
    );

    expect($result)
        ->toBe([
            'sections' => [],
            'variables' => [],
        ]);
});

it('returns null when the llm manager throws an exception', function (): void {
    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->andThrow(
            new RuntimeException('All eligible LLM providers failed.'),
        );

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->generateRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP',
    );

    expect($result)
        ->toBeNull();
});

it('returns null when the llm response is not structured', function (): void {
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

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->generateRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP',
    );

    expect($result)
        ->toBeNull();
});

it('repairs an invalid regulated template', function (): void {
    VariableDataType::query()->create([
        'code' => VariableDataType::TEXT,
        'name' => 'Text',
        'sort_order' => 10,
    ]);

    VariableDataType::query()->create([
        'code' => VariableDataType::DATE,
        'name' => 'Date',
        'sort_order' => 20,
    ]);

    $invalidTemplate = [
        'sections' => [
            [
                'title' => 'Purpose',
                'content' => 'Defines the purpose of the procedure.',
                'section_order' => 1,
                'section_type' => 'rich_text',
            ],
        ],
        'variables' => [
            [
                'name' => 'effective_date',
                'label' => 'Effective Date',
                'datatype' => VariableDataType::DATE,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $expectedResult = [
        'sections' => [
            [
                'title' => 'Purpose',
                'content' => 'This procedure becomes effective on {{effective_date}}.',
                'section_order' => 1,
                'section_type' => 'rich_text',
            ],
        ],
        'variables' => [
            [
                'name' => 'effective_date',
                'label' => 'Effective Date',
                'datatype' => VariableDataType::DATE,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $validationError = 'Generated template contains unreferenced variables: effective_date.';

    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->withArgs(
            function (LLMRequest $request) use (
                $validationError,
            ): bool {
                expect($request->useCase)
                    ->toBe(AIUseCase::REGULATED_TEMPLATE_GENERATION)
                    ->and($request->capability)
                    ->toBe(LLMCapability::STRUCTURED_OUTPUT)
                    ->and($request->dataClassification)
                    ->toBe(AIDataClassification::INTERNAL)
                    ->and($request->temperature)
                    ->toBe(0.0)
                    ->and($request->metadata)
                    ->toBe([
                        'feature' => 'regulated_template_repair',
                    ])
                    ->and($request->prompt)
                    ->toContain('Deviation Management Procedure')
                    ->toContain('Procedure for managing quality deviations.')
                    ->toContain('EU GMP')
                    ->toContain('VALIDATION FAILURE')
                    ->toContain($validationError)
                    ->toContain('INVALID GENERATED TEMPLATE')
                    ->toContain('"effective_date"')
                    ->toContain('REPAIR REQUIREMENTS')
                    ->toContain('{{variable_name}}');

                expect($request->jsonSchema)
                    ->toBeArray()
                    ->and($request->jsonSchema)
                    ->toHaveKey('properties.sections')
                    ->and($request->jsonSchema)
                    ->toHaveKey('properties.variables');

                return true;
            },
        )
        ->andReturn(
            new LLMResponse(
                content: $expectedResult,
                provider: 'gemini',
                model: 'gemini-test-model',
            ),
        );

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->repairRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP',
        generatedTemplate: $invalidTemplate,
        validationError: $validationError,
    );

    expect($result)
        ->toBe($expectedResult);
});

it('uses authorized variable data types in the repair schema', function (): void {
    VariableDataType::query()->create([
        'code' => VariableDataType::TEXT,
        'name' => 'Text',
        'sort_order' => 10,
    ]);

    VariableDataType::query()->create([
        'code' => VariableDataType::DATE,
        'name' => 'Date',
        'sort_order' => 20,
    ]);

    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->withArgs(function (LLMRequest $request): bool {
            $datatypeEnum = $request->jsonSchema['properties']
                ['variables']
                ['items']
                ['properties']
                ['datatype']
                ['enum'];

            expect($request->metadata)
                ->toBe([
                    'feature' => 'regulated_template_repair',
                ])
                ->and($request->temperature)
                ->toBe(0.0)
                ->and($datatypeEnum)
                ->toBe([
                    VariableDataType::DATE,
                    VariableDataType::TEXT,
                ]);

            return true;
        })
        ->andReturn(
            new LLMResponse(
                content: [
                    'sections' => [],
                    'variables' => [],
                ],
                provider: 'gemini',
                model: 'gemini-test-model',
            ),
        );

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->repairRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP',
        generatedTemplate: [
            'sections' => [],
            'variables' => [],
        ],
        validationError: 'Generated template validation failed.',
    );

    expect($result)
        ->toBe([
            'sections' => [],
            'variables' => [],
        ]);
});

it('returns null when regulated template repair fails', function (): void {
    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->andThrow(
            new RuntimeException('AI provider failure.'),
        );

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->repairRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP',
        generatedTemplate: [
            'sections' => [],
            'variables' => [],
        ],
        validationError: 'Generated template validation failed.',
    );

    expect($result)
        ->toBeNull();
});

it('returns null when the regulated template repair response is not structured', function (): void {
    $llmManager = Mockery::mock(LLMManagerContract::class);

    $llmManager
        ->shouldReceive('generate')
        ->once()
        ->andReturn(
            new LLMResponse(
                content: 'This is plain text instead of structured repair content.',
                provider: 'ollama',
                model: 'test-model',
            ),
        );

    $service = new TemplateGeneratorService(
        llmManager: $llmManager,
    );

    $result = $service->repairRegulatedTemplate(
        formData: [
            'name' => 'Deviation Management Procedure',
            'description' => 'Procedure for managing quality deviations.',
        ],
        regulationTags: 'EU GMP',
        generatedTemplate: [
            'sections' => [],
            'variables' => [],
        ],
        validationError: 'Generated template validation failed.',
    );

    expect($result)
        ->toBeNull();
});
