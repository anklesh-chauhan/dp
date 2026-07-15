<?php

declare(strict_types=1);

use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\AiExecutionStatus;
use App\Services\AI\Enums\LLMCapability;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('generates a ulid when an execution is created', function (): void {
    $execution = AiExecution::factory()->create();

    expect($execution->ulid)
        ->not->toBeNull()
        ->and(Str::isUlid($execution->ulid))
        ->toBeTrue();
});

it('preserves an explicitly provided ulid', function (): void {
    $ulid = (string) Str::ulid();

    $execution = AiExecution::factory()->create([
        'ulid' => $ulid,
    ]);

    expect($execution->ulid)->toBe($ulid);
});

it('casts execution attributes correctly', function (): void {
    $execution = AiExecution::factory()->create([
        'use_case' => AIUseCase::cases()[0],
        'capability' => LLMCapability::cases()[0],
        'status' => AiExecutionStatus::RUNNING,
        'attempt_count' => 2,
        'input_tokens' => 100,
        'output_tokens' => 50,
        'duration_ms' => 250,
    ]);

    expect($execution->use_case)
        ->toBeInstanceOf(AIUseCase::class)
        ->and($execution->capability)
        ->toBeInstanceOf(LLMCapability::class)
        ->and($execution->status)
        ->toBe(AiExecutionStatus::RUNNING)
        ->and($execution->attempt_count)
        ->toBeInt()
        ->toBe(2)
        ->and($execution->input_tokens)
        ->toBeInt()
        ->toBe(100)
        ->and($execution->output_tokens)
        ->toBeInt()
        ->toBe(50)
        ->and($execution->duration_ms)
        ->toBeInt()
        ->toBe(250);
});

it('casts execution lifecycle timestamps as immutable datetimes', function (): void {
    $execution = AiExecution::factory()->create([
        'started_at' => now(),
        'completed_at' => now(),
        'failed_at' => now(),
    ]);

    expect($execution->started_at)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($execution->completed_at)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($execution->failed_at)
        ->toBeInstanceOf(CarbonImmutable::class);
});

it('has many execution attempts', function (): void {
    $execution = AiExecution::factory()->create();

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $execution->getKey(),
        'sequence' => 1,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $execution->getKey(),
        'sequence' => 2,
    ]);

    expect($execution->attempts)
        ->toHaveCount(2);
});

it('casts attempt attributes correctly', function (): void {
    $attempt = AiExecutionAttempt::factory()->create([
        'sequence' => 2,
        'status' => AiExecutionStatus::SUCCEEDED,
        'input_tokens' => 100,
        'output_tokens' => 50,
        'duration_ms' => 250,
    ]);

    expect($attempt->ai_execution_id)
        ->toBeInt()
        ->and($attempt->sequence)
        ->toBeInt()
        ->toBe(2)
        ->and($attempt->status)
        ->toBe(AiExecutionStatus::SUCCEEDED)
        ->and($attempt->input_tokens)
        ->toBeInt()
        ->toBe(100)
        ->and($attempt->output_tokens)
        ->toBeInt()
        ->toBe(50)
        ->and($attempt->duration_ms)
        ->toBeInt()
        ->toBe(250);
});

it('casts attempt lifecycle timestamps as immutable datetimes', function (): void {
    $attempt = AiExecutionAttempt::factory()->create([
        'started_at' => now(),
        'completed_at' => now(),
        'failed_at' => now(),
    ]);

    expect($attempt->started_at)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($attempt->completed_at)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($attempt->failed_at)
        ->toBeInstanceOf(CarbonImmutable::class);
});

it('belongs to an execution', function (): void {
    $execution = AiExecution::factory()->create();

    $attempt = AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $execution->getKey(),
    ]);

    expect($attempt->execution->is($execution))
        ->toBeTrue();
});

it('deletes execution attempts when the execution is deleted', function (): void {
    $execution = AiExecution::factory()->create();

    $attempt = AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $execution->getKey(),
    ]);

    $execution->delete();

    expect(
        AiExecutionAttempt::query()
            ->whereKey($attempt->getKey())
            ->exists(),
    )->toBeFalse();
});

it('rejects duplicate attempt sequences within the same execution', function (): void {
    $execution = AiExecution::factory()->create();

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $execution->getKey(),
        'sequence' => 1,
    ]);

    expect(
        fn () => AiExecutionAttempt::factory()->create([
            'ai_execution_id' => $execution->getKey(),
            'sequence' => 1,
        ]),
    )->toThrow(QueryException::class);
});

it('allows the same attempt sequence across different executions', function (): void {
    $firstExecution = AiExecution::factory()->create();

    $secondExecution = AiExecution::factory()->create();

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $firstExecution->getKey(),
        'sequence' => 1,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $secondExecution->getKey(),
        'sequence' => 1,
    ]);

    expect(AiExecutionAttempt::query()->count())
        ->toBe(2);
});
