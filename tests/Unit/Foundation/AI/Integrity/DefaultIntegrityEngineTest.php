<?php

declare(strict_types=1);

use App\Foundation\AI\Contracts\RepairPolicy;
use App\Foundation\AI\Integrity\DefaultIntegrityEngine;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\Contracts\RepairService;
use App\Foundation\AI\Validation\Contracts\ValidationEngine;
use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

it('returns immediately when initial validation passes', function (): void {
    $artifact = ['title' => 'Document'];

    $context = new ValidationContext('generic_artifact');

    $rules = new ValidationRuleCollection();

    $validationResult = new ValidationResult();

    $validationEngine = Mockery::mock(ValidationEngine::class);

    $validationEngine
        ->shouldReceive('validate')
        ->once()
        ->with($artifact, $context, $rules)
        ->andReturn($validationResult);

    $repairPolicy = Mockery::mock(RepairPolicy::class);

    $repairPolicy
        ->shouldNotReceive('shouldRepair');

    $repairService = Mockery::mock(RepairService::class);

    $repairService
        ->shouldNotReceive('repair');

    $engine = new DefaultIntegrityEngine(
        $validationEngine,
        $repairService,
        $repairPolicy,
    );

    $result = $engine->process(
        $artifact,
        $context,
        $rules,
    );

    expect($result->isValid())->toBeTrue()
        ->and($result->repairAttempted())->toBeFalse()
        ->and($result->wasRevalidated())->toBeFalse();
});

it('returns validation result when repair policy rejects repair', function (): void {
    $artifact = ['title' => 'Document'];

    $context = new ValidationContext('generic_artifact');

    $rules = new ValidationRuleCollection();

    $validationResult = new ValidationResult(
        new ValidationIssueCollection([
            ValidationIssueData::error(
                'missing_title',
                'Missing title',
            ),
        ]),
    );

    $validationEngine = Mockery::mock(ValidationEngine::class);

    $validationEngine
        ->shouldReceive('validate')
        ->once()
        ->andReturn($validationResult);

    $repairPolicy = Mockery::mock(RepairPolicy::class);

    $repairPolicy
        ->shouldReceive('shouldRepair')
        ->once()
        ->with($artifact, $validationResult, $context)
        ->andReturnFalse();

    $repairService = Mockery::mock(RepairService::class);

    $repairService
        ->shouldNotReceive('repair');

    $engine = new DefaultIntegrityEngine(
        $validationEngine,
        $repairService,
        $repairPolicy,
    );

    $result = $engine->process(
        $artifact,
        $context,
        $rules,
    );

    expect($result->isValid())->toBeFalse()
        ->and($result->repairAttempted())->toBeFalse();
});

it('repairs and revalidates the artifact', function (): void {
    $artifact = ['title' => ''];

    $repairedArtifact = ['title' => 'Valid'];

    $context = new ValidationContext('generic_artifact');

    $rules = new ValidationRuleCollection();

    $failedValidation = new ValidationResult(
        new ValidationIssueCollection([
            ValidationIssueData::error(
                'missing_title',
                'Missing title',
            ),
        ]),
    );

    $passedValidation = new ValidationResult();

    $repairResult = new RepairResult(
        artifact: $repairedArtifact,
        successful: true,
        modified: true,
    );

    $validationEngine = Mockery::mock(ValidationEngine::class);

    $validationEngine
        ->shouldReceive('validate')
        ->once()
        ->with($artifact, $context, $rules)
        ->andReturn($failedValidation);

    $validationEngine
        ->shouldReceive('validate')
        ->once()
        ->with($repairedArtifact, $context, $rules)
        ->andReturn($passedValidation);

    $repairPolicy = Mockery::mock(RepairPolicy::class);

    $repairPolicy
        ->shouldReceive('shouldRepair')
        ->once()
        ->andReturnTrue();

    $repairService = Mockery::mock(RepairService::class);

    $repairService
        ->shouldReceive('repair')
        ->once()
        ->with($artifact, $failedValidation, $context)
        ->andReturn($repairResult);

    $engine = new DefaultIntegrityEngine(
        $validationEngine,
        $repairService,
        $repairPolicy,
    );

    $result = $engine->process(
        $artifact,
        $context,
        $rules,
    );

    expect($result->isValid())->toBeTrue()
        ->and($result->repairAttempted())->toBeTrue()
        ->and($result->repairSucceeded())->toBeTrue()
        ->and($result->wasRevalidated())->toBeTrue();
});

it('returns failed validation after unsuccessful repair', function (): void {
    $artifact = ['title' => ''];

    $context = new ValidationContext('generic_artifact');

    $rules = new ValidationRuleCollection();

    $failedValidation = new ValidationResult(
        new ValidationIssueCollection([
            ValidationIssueData::error(
                'missing_title',
                'Missing title',
            ),
        ]),
    );

    $repairResult = new RepairResult(
        artifact: $artifact,
        successful: false,
        modified: false,
    );

    $validationEngine = Mockery::mock(ValidationEngine::class);

    $validationEngine
        ->shouldReceive('validate')
        ->twice()
        ->andReturn($failedValidation);

    $repairPolicy = Mockery::mock(RepairPolicy::class);

    $repairPolicy
        ->shouldReceive('shouldRepair')
        ->once()
        ->andReturnTrue();

    $repairService = Mockery::mock(RepairService::class);

    $repairService
        ->shouldReceive('repair')
        ->once()
        ->andReturn($repairResult);

    $engine = new DefaultIntegrityEngine(
        $validationEngine,
        $repairService,
        $repairPolicy,
    );

    $result = $engine->process(
        $artifact,
        $context,
        $rules,
    );

    expect($result->isValid())->toBeFalse()
        ->and($result->repairAttempted())->toBeTrue()
        ->and($result->repairSucceeded())->toBeFalse()
        ->and($result->wasRevalidated())->toBeTrue();
});
