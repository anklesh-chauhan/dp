<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\RepairStrategy;
use App\Foundation\AI\Validation\Exceptions\RepairFailedException;
use App\Foundation\AI\Validation\Services\DefaultRepairExecutor;
use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

it('executes a supported strategy', function (): void {
    $artifact = ['foo' => 'bar'];

    $validationResult = new ValidationResult(
        new ValidationIssueCollection(),
    );

    $context = new ValidationContext('test');

    $expected = new RepairResult(
        artifact: ['fixed' => true],
        successful: true,
        modified: true,
    );

    $strategy = Mockery::mock(RepairStrategy::class);

    $strategy->shouldReceive('supports')
        ->once()
        ->andReturn(true);

    $strategy->shouldReceive('repair')
        ->once()
        ->andReturn($expected);

    $executor = new DefaultRepairExecutor();

    $result = $executor->execute(
        $strategy,
        $artifact,
        $validationResult,
        $context,
    );

    expect($result)->toBe($expected);
});

it('does not execute an unsupported strategy', function (): void {
    $artifact = [];

    $validationResult = new ValidationResult(
        new ValidationIssueCollection(),
    );

    $context = new ValidationContext('test');

    $strategy = Mockery::mock(RepairStrategy::class);

    $strategy->shouldReceive('supports')
        ->once()
        ->andReturn(false);

    $strategy->shouldNotReceive('repair');

    $executor = new DefaultRepairExecutor();

    $result = $executor->execute(
        $strategy,
        $artifact,
        $validationResult,
        $context,
    );

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->wasModified())->toBeFalse()
        ->and($result->artifact())->toBe($artifact);
});

it('returns the strategy repair result', function (): void {
    $artifact = [];

    $validationResult = new ValidationResult(
        new ValidationIssueCollection(),
    );

    $context = new ValidationContext('test');

    $expected = new RepairResult(
        artifact: ['hello' => 'world'],
        successful: true,
        modified: true,
    );

    $strategy = Mockery::mock(RepairStrategy::class);

    $strategy->shouldReceive('supports')->andReturn(true);
    $strategy->shouldReceive('repair')->andReturn($expected);

    $executor = new DefaultRepairExecutor();

    expect(
        $executor->execute(
            $strategy,
            $artifact,
            $validationResult,
            $context,
        )
    )->toBe($expected);
});

it('wraps unexpected exceptions', function (): void {
    $artifact = [];

    $validationResult = new ValidationResult(
        new ValidationIssueCollection(),
    );

    $context = new ValidationContext('test');

    $strategy = Mockery::mock(RepairStrategy::class);

    $strategy->shouldReceive('supports')->andReturn(true);

    $strategy->shouldReceive('repair')
        ->andThrow(new RuntimeException('Boom'));

    $executor = new DefaultRepairExecutor();

    expect(fn () => $executor->execute(
        $strategy,
        $artifact,
        $validationResult,
        $context,
    ))->toThrow(RepairFailedException::class);
});
