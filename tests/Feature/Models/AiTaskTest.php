<?php

declare(strict_types=1);

use App\Models\AiTask;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\AiTaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;

uses(RefreshDatabase::class);

it('creates an ai task with a generated uuid', function (): void {
    $task = AiTask::factory()->create();

    expect($task)
        ->uuid->not->toBeEmpty()
        ->use_case->toBe(AIUseCase::DOCUMENT_DESCRIPTION_GENERATION)
        ->status->toBe(AiTaskStatus::PENDING)
        ->input->toBeArray()
        ->result->toBeNull()
        ->progress->toBe(0)
        ->started_at->toBeNull()
        ->completed_at->toBeNull()
        ->failed_at->toBeNull();
});

it('marks a pending ai task as processing', function (): void {
    $task = AiTask::factory()->create();

    $task->markAsProcessing(
        currentStep: 'Generating document description',
        progress: 10,
    );

    expect($task)
        ->status->toBe(AiTaskStatus::PROCESSING)
        ->progress->toBe(10)
        ->current_step->toBe('Generating document description')
        ->started_at->not->toBeNull()
        ->error_message->toBeNull();
});

it('preserves the original started timestamp when processing is updated', function (): void {
    $task = AiTask::factory()
        ->processing()
        ->create();

    $startedAt = $task->started_at;

    $task->markAsProcessing(
        currentStep: 'Classifying document category',
        progress: 50,
    );

    expect($task)
        ->started_at->toEqual($startedAt)
        ->progress->toBe(50)
        ->current_step->toBe('Classifying document category');
});

it('updates progress for a processing ai task', function (): void {
    $task = AiTask::factory()
        ->processing()
        ->create();

    $task->updateProgress(
        progress: 75,
        currentStep: 'Classifying document type',
    );

    expect($task)
        ->status->toBe(AiTaskStatus::PROCESSING)
        ->progress->toBe(75)
        ->current_step->toBe('Classifying document type');
});

it('normalizes progress to the valid range', function (): void {
    $task = AiTask::factory()
        ->processing()
        ->create();

    $task->updateProgress(
        progress: 150,
        currentStep: 'Processing',
    );

    expect($task->progress)->toBe(100);

    $task->updateProgress(
        progress: -50,
        currentStep: 'Processing',
    );

    expect($task->progress)->toBe(0);
});

it('marks an ai task as completed', function (): void {
    $task = AiTask::factory()
        ->processing()
        ->create();

    $result = [
        'description' => 'Generated description.',
        'category_id' => 1,
        'document_type_id' => 2,
        'regulation_tag_ids' => [
            3,
            4,
        ],
    ];

    $task->markAsCompleted(
        result: $result,
        provider: 'ollama',
        model: 'qwen2.5:7b',
    );

    expect($task)
        ->status->toBe(AiTaskStatus::COMPLETED)
        ->result->toBe($result)
        ->provider->toBe('ollama')
        ->model->toBe('qwen2.5:7b')
        ->progress->toBe(100)
        ->current_step->toBeNull()
        ->error_message->toBeNull()
        ->completed_at->not->toBeNull()
        ->failed_at->toBeNull();
});

it('marks an ai task as failed', function (): void {
    $task = AiTask::factory()
        ->processing()
        ->create();

    $task->markAsFailed(
        'All eligible AI providers failed.',
    );

    expect($task)
        ->status->toBe(AiTaskStatus::FAILED)
        ->error_message->toBe('All eligible AI providers failed.')
        ->current_step->toBeNull()
        ->failed_at->not->toBeNull()
        ->completed_at->toBeNull();
});

it('marks an active ai task as cancelled', function (): void {
    $task = AiTask::factory()
        ->processing()
        ->create();

    $task->markAsCancelled();

    expect($task)
        ->status->toBe(AiTaskStatus::CANCELLED)
        ->current_step->toBeNull()
        ->isFinished()->toBeTrue();
});

it('prevents a completed ai task from returning to processing', function (): void {
    $task = AiTask::factory()
        ->completed()
        ->create();

    expect(
        fn () => $task->markAsProcessing(
            currentStep: 'Restarting',
        ),
    )->toThrow(LogicException::class);
});

it('prevents a failed ai task from being completed', function (): void {
    $task = AiTask::factory()
        ->failed()
        ->create();

    expect(
        fn () => $task->markAsCompleted(
            result: [
                'description' => 'Invalid late result.',
            ],
        ),
    )->toThrow(LogicException::class);
});

it('prevents a pending ai task from updating progress directly', function (): void {
    $task = AiTask::factory()->create();

    expect(
        fn () => $task->updateProgress(
            progress: 50,
            currentStep: 'Invalid transition',
        ),
    )->toThrow(LogicException::class);
});

it('reports its lifecycle state correctly', function (): void {
    $pending = AiTask::factory()->create();

    $processing = AiTask::factory()
        ->processing()
        ->create();

    $completed = AiTask::factory()
        ->completed()
        ->create();

    $failed = AiTask::factory()
        ->failed()
        ->create();

    expect($pending)
        ->isPending()->toBeTrue()
        ->isProcessing()->toBeFalse()
        ->isFinished()->toBeFalse()

        ->and($processing)
        ->isPending()->toBeFalse()
        ->isProcessing()->toBeTrue()
        ->isFinished()->toBeFalse()

        ->and($completed)
        ->isCompleted()->toBeTrue()
        ->isFinished()->toBeTrue()

        ->and($failed)
        ->isFailed()->toBeTrue()
        ->isFinished()->toBeTrue();
});
