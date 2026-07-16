<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Contracts\RepairPipeline;
use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;
use App\Foundation\AI\Validation\Services\DefaultRepairService;
use Mockery;

it('delegates repair to the pipeline', function (): void {
    $artifact = [];

    $validationResult = new ValidationResult(
        new ValidationIssueCollection(),
    );

    $context = new ValidationContext('test');

    $expected = new RepairResult(
        artifact: ['fixed' => true],
        successful: true,
        modified: true,
    );

    $pipeline = Mockery::mock(RepairPipeline::class);

    $pipeline->shouldReceive('repair')
        ->once()
        ->with(
            $artifact,
            $validationResult,
            $context,
        )
        ->andReturn($expected);

    $service = new DefaultRepairService($pipeline);

    expect(
        $service->repair(
            $artifact,
            $validationResult,
            $context,
        )
    )->toBe($expected);
});
