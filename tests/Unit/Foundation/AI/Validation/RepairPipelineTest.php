<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Contracts\RepairExecutor;
use App\Foundation\AI\Validation\Contracts\RepairStrategy;
use App\Foundation\AI\Validation\Pipeline\RepairPipeline;
use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

it('returns the first successful repair', function (): void {
    $artifact = [];

    $validationResult = new ValidationResult(
        new ValidationIssueCollection(),
    );

    $context = new ValidationContext('test');

    $strategyA = Mockery::mock(RepairStrategy::class);
    $strategyB = Mockery::mock(RepairStrategy::class);

    $expected = new RepairResult(
        artifact: ['fixed' => true],
        successful: true,
        modified: true,
    );

    $executor = Mockery::mock(RepairExecutor::class);

    $executor->shouldReceive('execute')
        ->once()
        ->with($strategyA, Mockery::any(), Mockery::any(), Mockery::any())
        ->andReturn(
            new RepairResult([], false, false)
        );

    $executor->shouldReceive('execute')
        ->once()
        ->with($strategyB, Mockery::any(), Mockery::any(), Mockery::any())
        ->andReturn($expected);

    $pipeline = new RepairPipeline(
        [$strategyA, $strategyB],
        $executor,
    );

    expect(
        $pipeline->repair(
            $artifact,
            $validationResult,
            $context,
        )
    )->toBe($expected);
});

it('returns failure when no strategy succeeds', function (): void {
    $artifact = [];

    $validationResult = new ValidationResult(
        new ValidationIssueCollection(),
    );

    $context = new ValidationContext('test');

    $strategy = Mockery::mock(RepairStrategy::class);

    $executor = Mockery::mock(RepairExecutor::class);

    $executor->shouldReceive('execute')
        ->once()
        ->andReturn(
            new RepairResult(
                artifact: $artifact,
                successful: false,
                modified: false,
            )
        );

    $pipeline = new RepairPipeline([$strategy], $executor);

    $result = $pipeline->repair(
        $artifact,
        $validationResult,
        $context,
    );

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->artifact())->toBe($artifact);
});

it('returns failure when there are no strategies', function (): void {
    $artifact = [];

    $validationResult = new ValidationResult(
        new ValidationIssueCollection(),
    );

    $context = new ValidationContext('test');

    $executor = Mockery::mock(RepairExecutor::class);

    $pipeline = new RepairPipeline([], $executor);

    $result = $pipeline->repair(
        $artifact,
        $validationResult,
        $context,
    );

    expect($result->isSuccessful())->toBeFalse();
});
