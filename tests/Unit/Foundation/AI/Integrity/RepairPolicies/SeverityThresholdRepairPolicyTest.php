<?php

declare(strict_types=1);

use App\Foundation\AI\Integrity\RepairPolicies\SeverityThresholdRepairPolicy;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

it('repairs when validation severity meets the configured error threshold', function (
    mixed $artifact,
    ValidationResult $result,
    bool $expected,
): void {
    $policy = new SeverityThresholdRepairPolicy(
        ValidationSeverity::ERROR,
    );

    $context = new ValidationContext('generic_artifact');

    expect(
        $policy->shouldRepair(
            [],
            $result,
            $context,
        )
    )->toBe($expected);
})->with([
    'no issues' => [
        new stdClass(),
        new ValidationResult(),
        false,
    ],

    'info' => [
        new stdClass(),
        new ValidationResult(
            new ValidationIssueCollection([
                ValidationIssueData::info(
                    'info',
                    'Information',
                ),
            ]),
        ),
        false,
    ],

    'warning' => [
        new stdClass(),
        new ValidationResult(
            new ValidationIssueCollection([
                ValidationIssueData::warning(
                    'warning',
                    'Warning',
                ),
            ]),
        ),
        false,
    ],

    'error' => [
        new stdClass(),
        new ValidationResult(
            new ValidationIssueCollection([
                ValidationIssueData::error(
                    'error',
                    'Error',
                ),
            ]),
        ),
        true,
    ],

    'critical' => [
        new stdClass(),
        new ValidationResult(
            new ValidationIssueCollection([
                ValidationIssueData::critical(
                    'critical',
                    'Critical',
                ),
            ]),
        ),
        true,
    ],
]);

it('repairs when validation severity meets the configured warning threshold', function (
    mixed $artifact,
    ValidationResult $result,
    bool $expected,
): void {
    $policy = new SeverityThresholdRepairPolicy(
        ValidationSeverity::WARNING,
    );

    $context = new ValidationContext('generic_artifact');

    expect(
        $policy->shouldRepair($artifact, $result, $context)
    )->toBe($expected);
})->with([
    'no issues' => [
        new stdClass(),
        new ValidationResult(),
        false,
    ],

    'info' => [
        new stdClass(),
        new ValidationResult(
            new ValidationIssueCollection([
                ValidationIssueData::info(
                    'info',
                    'Information',
                ),
            ]),
        ),
        false,
    ],

    'warning' => [
        new stdClass(),
        new ValidationResult(
            new ValidationIssueCollection([
                ValidationIssueData::warning(
                    'warning',
                    'Warning',
                ),
            ]),
        ),
        true,
    ],

    'error' => [
        new stdClass(),
        new ValidationResult(
            new ValidationIssueCollection([
                ValidationIssueData::error(
                    'error',
                    'Error',
                ),
            ]),
        ),
        true,
    ],

    'critical' => [
        new stdClass(),
        new ValidationResult(
            new ValidationIssueCollection([
                ValidationIssueData::critical(
                    'critical',
                    'Critical',
                ),
            ]),
        ),
        true,
    ],
]);
