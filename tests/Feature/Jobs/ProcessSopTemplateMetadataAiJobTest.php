<?php

declare(strict_types=1);

use App\Jobs\ProcessSopTemplateMetadataAiJob;
use App\Models\AiTask;
use App\Services\AI\Contracts\DocumentClassifier;
use App\Services\AI\Contracts\DocumentDescriptionGenerator;
use App\Services\AI\Enums\AiTaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('processes sop template metadata and stores the completed result', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->with(
            'Equipment Cleaning Procedure',
            'Production',
        )
        ->andReturn([
            'description' => 'Defines the controlled process for cleaning manufacturing equipment.',
            'reasoning' => 'The description reflects Production department responsibilities.',
        ]);

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldReceive('classify')
        ->once()
        ->with(
            'Equipment Cleaning Procedure',
            'Defines the controlled process for cleaning manufacturing equipment.',
            'Production',
        )
        ->andReturn([
            'category_id' => 10,
            'document_type_id' => 20,
            'regulation_tag_ids' => [
                30,
                40,
            ],
        ]);

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    $job->handle(
        descriptionGenerator: $descriptionGenerator,
        classifier: $classifier,
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::COMPLETED)
        ->progress->toBe(100)
        ->current_step->toBeNull()
        ->error_message->toBeNull()
        ->completed_at->not->toBeNull()
        ->failed_at->toBeNull()
        ->and($task->result)
        ->toBe([
            'description' => 'Defines the controlled process for cleaning manufacturing equipment.',
            'reasoning' => 'The description reflects Production department responsibilities.',
            'category_id' => 10,
            'document_type_id' => 20,
            'regulation_tag_ids' => [
                30,
                40,
            ],
        ]);
});

it('passes the generated description directly to document classification', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Environmental Monitoring Procedure',
            'department_name' => 'Quality Control',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->with(
            'Environmental Monitoring Procedure',
            'Quality Control',
        )
        ->andReturn([
            'description' => '  Generated environmental monitoring description.  ',
            'reasoning' => null,
        ]);

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldReceive('classify')
        ->once()
        ->with(
            'Environmental Monitoring Procedure',
            'Generated environmental monitoring description.',
            'Quality Control',
        )
        ->andReturn([
            'category_id' => 1,
            'document_type_id' => 2,
            'regulation_tag_ids' => [],
        ]);

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    $job->handle(
        descriptionGenerator: $descriptionGenerator,
        classifier: $classifier,
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::COMPLETED)
        ->and($task->result['description'])
        ->toBe('Generated environmental monitoring description.');
});

it('stores null when description reasoning is unavailable', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->andReturn([
            'description' => 'Generated description.',
            'reasoning' => '   ',
        ]);

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldReceive('classify')
        ->once()
        ->andReturn([
            'category_id' => 1,
            'document_type_id' => 2,
            'regulation_tag_ids' => [],
        ]);

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    $job->handle(
        descriptionGenerator: $descriptionGenerator,
        classifier: $classifier,
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::COMPLETED)
        ->and($task->result['reasoning'])
        ->toBeNull();
});

it('fails when the ai task name is missing', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldNotReceive('generate');

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldNotReceive('classify');

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    expect(
        fn () => $job->handle(
            descriptionGenerator: $descriptionGenerator,
            classifier: $classifier,
        ),
    )->toThrow(
        \RuntimeException::class,
        'AI task input [name] is required.',
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->error_message->toBe('AI task input [name] is required.')
        ->failed_at->not->toBeNull()
        ->completed_at->toBeNull();
});

it('fails when the ai task department name is missing', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldNotReceive('generate');

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldNotReceive('classify');

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    expect(
        fn () => $job->handle(
            descriptionGenerator: $descriptionGenerator,
            classifier: $classifier,
        ),
    )->toThrow(
        \RuntimeException::class,
        'AI task input [department_name] is required.',
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->error_message->toBe(
            'AI task input [department_name] is required.',
        )
        ->failed_at->not->toBeNull()
        ->completed_at->toBeNull();
});

it('fails when description generation returns no usable description', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->with(
            'Equipment Cleaning Procedure',
            'Production',
        )
        ->andReturn([
            'description' => null,
            'reasoning' => null,
        ]);

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldNotReceive('classify');

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    expect(
        fn () => $job->handle(
            descriptionGenerator: $descriptionGenerator,
            classifier: $classifier,
        ),
    )->toThrow(
        \RuntimeException::class,
        'AI description generation returned no usable description.',
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->error_message->toBe(
            'AI description generation returned no usable description.',
        )
        ->failed_at->not->toBeNull()
        ->completed_at->toBeNull();
});

it('fails when description generation returns a blank description', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->andReturn([
            'description' => '   ',
            'reasoning' => null,
        ]);

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldNotReceive('classify');

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    expect(
        fn () => $job->handle(
            descriptionGenerator: $descriptionGenerator,
            classifier: $classifier,
        ),
    )->toThrow(
        \RuntimeException::class,
        'AI description generation returned no usable description.',
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->failed_at->not->toBeNull();
});

it('fails when document classification returns an incomplete result', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->andReturn([
            'description' => 'Generated description.',
            'reasoning' => 'Generated reasoning.',
        ]);

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldReceive('classify')
        ->once()
        ->andReturn([
            'category_id' => null,
            'document_type_id' => null,
            'regulation_tag_ids' => [],
        ]);

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    expect(
        fn () => $job->handle(
            descriptionGenerator: $descriptionGenerator,
            classifier: $classifier,
        ),
    )->toThrow(
        \RuntimeException::class,
        'AI document classification returned an incomplete result.',
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->error_message->toBe(
            'AI document classification returned an incomplete result.',
        )
        ->failed_at->not->toBeNull()
        ->completed_at->toBeNull();
});

it('fails when regulation tag ids are not returned as an array', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->andReturn([
            'description' => 'Generated description.',
            'reasoning' => null,
        ]);

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldReceive('classify')
        ->once()
        ->andReturn([
            'category_id' => 1,
            'document_type_id' => 2,
            'regulation_tag_ids' => null,
        ]);

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    expect(
        fn () => $job->handle(
            descriptionGenerator: $descriptionGenerator,
            classifier: $classifier,
        ),
    )->toThrow(
        \RuntimeException::class,
        'AI document classification returned an incomplete result.',
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->failed_at->not->toBeNull();
});

it('fails the task when description generation throws an exception', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->andThrow(
            new \RuntimeException('All LLM providers failed.'),
        );

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldNotReceive('classify');

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    expect(
        fn () => $job->handle(
            descriptionGenerator: $descriptionGenerator,
            classifier: $classifier,
        ),
    )->toThrow(
        \RuntimeException::class,
        'All LLM providers failed.',
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->error_message->toBe('All LLM providers failed.')
        ->failed_at->not->toBeNull()
        ->completed_at->toBeNull();
});

it('fails the task when document classification throws an exception', function (): void {
    $task = AiTask::factory()->create([
        'input' => [
            'name' => 'Equipment Cleaning Procedure',
            'department_name' => 'Production',
        ],
    ]);

    $descriptionGenerator = Mockery::mock(
        DocumentDescriptionGenerator::class,
    );

    $descriptionGenerator
        ->shouldReceive('generate')
        ->once()
        ->andReturn([
            'description' => 'Generated description.',
            'reasoning' => null,
        ]);

    $classifier = Mockery::mock(
        DocumentClassifier::class,
    );

    $classifier
        ->shouldReceive('classify')
        ->once()
        ->andThrow(
            new \RuntimeException('Document classification failed.'),
        );

    $job = new ProcessSopTemplateMetadataAiJob(
        aiTaskId: $task->getKey(),
    );

    expect(
        fn () => $job->handle(
            descriptionGenerator: $descriptionGenerator,
            classifier: $classifier,
        ),
    )->toThrow(
        \RuntimeException::class,
        'Document classification failed.',
    );

    $task->refresh();

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->error_message->toBe('Document classification failed.')
        ->failed_at->not->toBeNull()
        ->completed_at->toBeNull();
});
