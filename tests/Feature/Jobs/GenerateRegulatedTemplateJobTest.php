<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\AI\Services\DocumentTemplateIntegrityService;
use App\Jobs\GenerateRegulatedTemplateJob;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVariable;
use App\Models\TemplateStatus;
use App\Models\VariableDataType;
use App\Services\AI\Contracts\TemplateGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    TemplateStatus::query()->create([
        'code' => TemplateStatus::DRAFT,
        'name' => 'Draft',
    ]);
});

function handleGenerateRegulatedTemplateJob(
    GenerateRegulatedTemplateJob $job,
    TemplateGenerator $generator,
): void {
    $job->handle(
        aiService: $generator,
        integrityService: app(
            DocumentTemplateIntegrityService::class,
        ),
    );
}

function createGenerationTemplate(
    array $attributes = [],
): DocumentTemplate {
    return DocumentTemplate::factory()->create([
        'template_status_id' => TemplateStatus::idFor(
            TemplateStatus::DRAFT,
        ),
        'current_version' => 0,
        'generation_status' => DocumentTemplate::GENERATION_STATUS_PROCESSING,
        'generation_progress' => 0,
        ...$attributes,
    ]);
}

function createVariableDataType(
    string $code,
    string $name,
    int $sortOrder,
): VariableDataType {
    return VariableDataType::query()->create([
        'code' => $code,
        'name' => $name,
        'sort_order' => $sortOrder,
    ]);
}

/**
 * @return array<string, mixed>
 */
function successfulTemplateGenerationResult(): array
{
    return [
        'sections' => [
            [
                'title' => 'Purpose',
                'content' => 'Defines the purpose of deviation {{deviation_number}}.',
                'section_order' => 1,
                'section_type' => 'rich_text',
            ],
            [
                'title' => 'Scope',
                'content' => 'The deviation was recorded on {{deviation_date}}.',
                'section_order' => 2,
                'section_type' => 'rich_text',
            ],
        ],
        'variables' => [
            [
                'name' => 'deviation_number',
                'label' => 'Deviation Number',
                'datatype' => VariableDataType::TEXT,
                'default_value' => '',
                'required' => true,
            ],
            [
                'name' => 'deviation_date',
                'label' => 'Deviation Date',
                'datatype' => VariableDataType::DATE,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];
}

it('generates and persists a regulated template', function (): void {
    $textDataType = createVariableDataType(
        VariableDataType::TEXT,
        'Text',
        10,
    );

    $dateDataType = createVariableDataType(
        VariableDataType::DATE,
        'Date',
        20,
    );

    $template = createGenerationTemplate([
        'name' => 'Deviation Management Procedure',
    ]);

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->withArgs(
            function (
                array $formData,
                string $regulationTags,
            ) use ($template): bool {
                expect($formData['id'])
                    ->toBe($template->getKey())
                    ->and($formData['name'])
                    ->toBe('Deviation Management Procedure')
                    ->and($formData['category']['id'])
                    ->toBe($template->category_id)
                    ->and($formData['document_type']['id'])
                    ->toBe($template->document_type_id)
                    ->and($regulationTags)
                    ->toBe('EU GMP, FDA 21 CFR Part 211');

                return true;
            },
        )
        ->andReturn(successfulTemplateGenerationResult());

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP, FDA 21 CFR Part 211',
    );

    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );

    $template->refresh();

    expect($template->current_version)
        ->toBe(1)
        ->and($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_COMPLETED)
        ->and($template->generation_progress)
        ->toBe(100);

    $version = $template
        ->versions()
        ->where('version', 1)
        ->first();

    expect($version)
        ->not->toBeNull()
        ->and($version->template_status_id)
        ->toBe(TemplateStatus::idFor(TemplateStatus::DRAFT))
        ->and($version->change_reason)
        ->toBe(
            'Auto-generated base boilerplate compliant with specified regulation tags.',
        );

    $sections = $version->sections;

    expect($sections)
        ->toHaveCount(2)
        ->and($sections[0]->title)
        ->toBe('Purpose')
        ->and($sections[0]->section_order)
        ->toBe(1)
        ->and($sections[0]->is_required)
        ->toBeTrue()
        ->and($sections[1]->title)
        ->toBe('Scope')
        ->and($sections[1]->section_order)
        ->toBe(2);

    $variables = $version->variables->keyBy('name');

    expect($variables)
        ->toHaveCount(2)
        ->and($variables)
        ->toHaveKeys([
            'deviation_number',
            'deviation_date',
        ]);

    expect($variables['deviation_number']->variable_data_type_id)
        ->toBe($textDataType->getKey())
        ->and($variables['deviation_number']->required)
        ->toBeTrue()
        ->and($variables['deviation_date']->variable_data_type_id)
        ->toBe($dateDataType->getKey());
});

it('marks template generation as failed when the generator returns null', function (): void {
    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturnNull();

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0)
        ->and($template->current_version)
        ->toBe(0)
        ->and($template->versions)
        ->toHaveCount(0);
});

it('marks template generation as failed and rethrows when the generator throws an exception', function (): void {
    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andThrow(
            new RuntimeException('AI provider failure.'),
        );

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        RuntimeException::class,
        'AI provider failure.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0)
        ->and($template->versions)
        ->toHaveCount(0);
});

it('fails when generated sections are missing', function (): void {
    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn([
            'variables' => [],
        ]);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        RuntimeException::class,
        'AI template generation returned invalid sections.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0)
        ->and($template->versions)
        ->toHaveCount(0);
});

it('fails when generated variables are missing', function (): void {
    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn([
            'sections' => [],
        ]);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        RuntimeException::class,
        'AI template generation returned invalid variables.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0)
        ->and($template->versions)
        ->toHaveCount(0);
});

it('fails when generated sections are not an array', function (): void {
    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn([
            'sections' => 'invalid',
            'variables' => [],
        ]);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        RuntimeException::class,
        'AI template generation returned invalid sections.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0);
});

it('fails when generated variables are not an array', function (): void {
    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn([
            'sections' => [],
            'variables' => 'invalid',
        ]);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        RuntimeException::class,
        'AI template generation returned invalid variables.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0);
});

it('resolves variable data types case insensitively', function (): void {
    $dateDataType = createVariableDataType(
        VariableDataType::DATE,
        'Date',
        10,
    );

    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn([
            'sections' => [
                [
                    'title' => 'Effective Date',
                    'content' => 'This document becomes effective on {{effective_date}}.',
                    'section_order' => 1,
                    'section_type' => 'rich_text',
                ],
            ],
            'variables' => [
                [
                    'name' => 'effective_date',
                    'label' => 'Effective Date',
                    'datatype' => 'DATE',
                    'default_value' => '',
                    'required' => true,
                ],
            ],
        ]);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );

    $variable = $template
        ->versions()
        ->firstOrFail()
        ->variables()
        ->firstOrFail();

    expect($variable->variable_data_type_id)
        ->toBe($dateDataType->getKey());
});

it('falls back to text for an unknown variable data type', function (): void {
    $textDataType = createVariableDataType(
        VariableDataType::TEXT,
        'Text',
        10,
    );

    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn([
            'sections' => [
                [
                    'title' => 'Unknown Field',
                    'content' => 'The generated value is {{unknown_field}}.',
                    'section_order' => 1,
                    'section_type' => 'rich_text',
                ],
            ],
            'variables' => [
                [
                    'name' => 'unknown_field',
                    'label' => 'Unknown Field',
                    'datatype' => 'unsupported_type',
                    'default_value' => '',
                    'required' => false,
                ],
            ],
        ]);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );

    $variable = $template
        ->versions()
        ->firstOrFail()
        ->variables()
        ->firstOrFail();

    expect($variable->variable_data_type_id)
        ->toBe($textDataType->getKey());
});

it('fails and rolls back when no variable data type can be resolved', function (): void {
    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn([
            'sections' => [
                [
                    'title' => 'Purpose',
                    'content' => 'Purpose content for {{unknown_field}}.',
                    'section_order' => 1,
                    'section_type' => 'rich_text',
                ],
            ],
            'variables' => [
                [
                    'name' => 'unknown_field',
                    'label' => 'Unknown Field',
                    'datatype' => 'unsupported_type',
                    'default_value' => '',
                    'required' => false,
                ],
            ],
        ]);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        RuntimeException::class,
        'Unable to resolve variable data type [unsupported_type] and no text fallback exists.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0)
        ->and($template->current_version)
        ->toBe(0)
        ->and($template->versions)
        ->toHaveCount(0);

    expect(DocumentTemplateSection::query()->count())
        ->toBe(0)
        ->and(DocumentTemplateVariable::query()->count())
        ->toBe(0);
});

it('replaces generated sections and variables when the job is retried', function (): void {
    createVariableDataType(
        VariableDataType::TEXT,
        'Text',
        10,
    );

    createVariableDataType(
        VariableDataType::DATE,
        'Date',
        20,
    );

    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->twice()
        ->andReturn(successfulTemplateGenerationResult());

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );
    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );

    $template->refresh();

    expect($template->versions)
        ->toHaveCount(1);

    $version = $template->versions->firstOrFail();

    expect($version->sections)
        ->toHaveCount(2)
        ->and($version->variables)
        ->toHaveCount(2)
        ->and($template->current_version)
        ->toBe(1)
        ->and($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_COMPLETED)
        ->and($template->generation_progress)
        ->toBe(100);
});

it('propagates the template creator to the generated version', function (): void {
    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn([
            'sections' => [],
            'variables' => [],
        ]);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );

    $version = $template
        ->versions()
        ->firstOrFail();

    expect($version->created_by)
        ->toBe($template->created_by);
});

it('fails before persistence when an unreferenced variable cannot be repaired', function (): void {
    createVariableDataType(
        VariableDataType::TEXT,
        'Text',
        10,
    );

    $template = createGenerationTemplate();

    $invalidResult = [
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
                'name' => 'deviation_number',
                'label' => 'Deviation Number',
                'datatype' => VariableDataType::TEXT,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn($invalidResult);

    $generator
        ->shouldReceive('repairRegulatedTemplate')
        ->once()
        ->withArgs(
            function (
                array $formData,
                string $regulationTags,
                array $generatedTemplate,
                string $validationError,
            ) use (
                $template,
                $invalidResult,
            ): bool {
                expect($formData['id'])
                    ->toBe($template->getKey())
                    ->and($regulationTags)
                    ->toBe('EU GMP')
                    ->and($generatedTemplate)
                    ->toBe($invalidResult)
                    ->and($validationError)
                    ->toBe(
                        'Generated template contains unreferenced variables: deviation_number.',
                    );

                return true;
            },
        )
        ->andReturnNull();

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        RuntimeException::class,
        'AI template repair failed to return a structured result.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0)
        ->and($template->current_version)
        ->toBe(0)
        ->and($template->versions)
        ->toHaveCount(0);

    expect(DocumentTemplateSection::query()->count())
        ->toBe(0)
        ->and(DocumentTemplateVariable::query()->count())
        ->toBe(0);
});

it('fails before persistence when an undefined placeholder cannot be repaired', function (): void {
    createVariableDataType(
        VariableDataType::TEXT,
        'Text',
        10,
    );

    $template = createGenerationTemplate();

    $invalidResult = [
        'sections' => [
            [
                'title' => 'Purpose',
                'content' => 'Deviation {{deviation_number}} was approved by {{approved_by}}.',
                'section_order' => 1,
                'section_type' => 'rich_text',
            ],
        ],
        'variables' => [
            [
                'name' => 'deviation_number',
                'label' => 'Deviation Number',
                'datatype' => VariableDataType::TEXT,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn($invalidResult);

    $generator
        ->shouldReceive('repairRegulatedTemplate')
        ->once()
        ->withArgs(
            function (
                array $formData,
                string $regulationTags,
                array $generatedTemplate,
                string $validationError,
            ) use (
                $template,
                $invalidResult,
            ): bool {
                expect($formData['id'])
                    ->toBe($template->getKey())
                    ->and($regulationTags)
                    ->toBe('EU GMP')
                    ->and($generatedTemplate)
                    ->toBe($invalidResult)
                    ->and($validationError)
                    ->toBe(
                        'Generated template contains undefined placeholders: approved_by.',
                    );

                return true;
            },
        )
        ->andReturnNull();

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        RuntimeException::class,
        'AI template repair failed to return a structured result.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0)
        ->and($template->current_version)
        ->toBe(0)
        ->and($template->versions)
        ->toHaveCount(0);

    expect(DocumentTemplateSection::query()->count())
        ->toBe(0)
        ->and(DocumentTemplateVariable::query()->count())
        ->toBe(0);
});

it('repairs an invalid generated template and persists the repaired result', function (): void {
    $textDataType = createVariableDataType(
        VariableDataType::TEXT,
        'Text',
        10,
    );

    $template = createGenerationTemplate();

    $invalidResult = [
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
                'name' => 'deviation_number',
                'label' => 'Deviation Number',
                'datatype' => VariableDataType::TEXT,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $repairedResult = [
        'sections' => [
            [
                'title' => 'Purpose',
                'content' => 'Defines the purpose of deviation {{deviation_number}}.',
                'section_order' => 1,
                'section_type' => 'rich_text',
            ],
        ],
        'variables' => [
            [
                'name' => 'deviation_number',
                'label' => 'Deviation Number',
                'datatype' => VariableDataType::TEXT,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn($invalidResult);

    $generator
        ->shouldReceive('repairRegulatedTemplate')
        ->once()
        ->withArgs(
            function (
                array $formData,
                string $regulationTags,
                array $generatedTemplate,
                string $validationError,
            ) use ($template, $invalidResult): bool {
                expect($formData['id'])
                    ->toBe($template->getKey())
                    ->and($regulationTags)
                    ->toBe('EU GMP')
                    ->and($generatedTemplate)
                    ->toBe($invalidResult)
                    ->and($validationError)
                    ->toBe(
                        'Generated template contains unreferenced variables: deviation_number.',
                    );

                return true;
            },
        )
        ->andReturn($repairedResult);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_COMPLETED)
        ->and($template->generation_progress)
        ->toBe(100)
        ->and($template->current_version)
        ->toBe(1)
        ->and($template->versions)
        ->toHaveCount(1);

    $version = $template
        ->versions()
        ->where('version', 1)
        ->firstOrFail();

    expect($version->sections)
        ->toHaveCount(1)
        ->and($version->sections->firstOrFail()->content)
        ->toBe(
            'Defines the purpose of deviation {{deviation_number}}.',
        );

    $variable = $version
        ->variables()
        ->firstOrFail();

    expect($variable->name)
        ->toBe('deviation_number')
        ->and($variable->variable_data_type_id)
        ->toBe($textDataType->getKey());

    expect(DocumentTemplateSection::query()->count())
        ->toBe(1)
        ->and(DocumentTemplateVariable::query()->count())
        ->toBe(1);
});

it('fails without another repair attempt when the repaired template is still invalid', function (): void {
    createVariableDataType(
        VariableDataType::TEXT,
        'Text',
        10,
    );

    $template = createGenerationTemplate();

    $invalidResult = [
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
                'name' => 'deviation_number',
                'label' => 'Deviation Number',
                'datatype' => VariableDataType::TEXT,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $stillInvalidRepairedResult = [
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
                'name' => 'deviation_number',
                'label' => 'Deviation Number',
                'datatype' => VariableDataType::TEXT,
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn($invalidResult);

    $generator
        ->shouldReceive('repairRegulatedTemplate')
        ->once()
        ->andReturn($stillInvalidRepairedResult);

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    expect(
        fn () => handleGenerateRegulatedTemplateJob(
            job: $job,
            generator: $generator,
        ),
    )->toThrow(
        InvalidArgumentException::class,
        'Generated template contains unreferenced variables: deviation_number.',
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_FAILED)
        ->and($template->generation_progress)
        ->toBe(0)
        ->and($template->current_version)
        ->toBe(0)
        ->and($template->versions)
        ->toHaveCount(0);

    expect(DocumentTemplateSection::query()->count())
        ->toBe(0)
        ->and(DocumentTemplateVariable::query()->count())
        ->toBe(0);
});

it('does not attempt repair when the initial generated template is valid', function (): void {
    createVariableDataType(
        VariableDataType::TEXT,
        'Text',
        10,
    );

    createVariableDataType(
        VariableDataType::DATE,
        'Date',
        20,
    );

    $template = createGenerationTemplate();

    $generator = Mockery::mock(TemplateGenerator::class);

    $generator
        ->shouldReceive('generateRegulatedTemplate')
        ->once()
        ->andReturn(successfulTemplateGenerationResult());

    $generator
        ->shouldNotReceive('repairRegulatedTemplate');

    $job = new GenerateRegulatedTemplateJob(
        template: $template,
        regulationTags: 'EU GMP',
    );

    handleGenerateRegulatedTemplateJob(
        job: $job,
        generator: $generator,
    );

    $template->refresh();

    expect($template->generation_status)
        ->toBe(DocumentTemplate::GENERATION_STATUS_COMPLETED)
        ->and($template->generation_progress)
        ->toBe(100)
        ->and($template->current_version)
        ->toBe(1)
        ->and($template->versions)
        ->toHaveCount(1);
});
