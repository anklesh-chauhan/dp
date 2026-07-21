<?php

declare(strict_types=1);

use App\Foundation\AI\Integrity\RepairPolicies\ErrorOnlyRepairPolicy;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

it('repairs only blocking validation results', function (
    ValidationResult $result,
    bool $expected,
): void {
    $policy = new ErrorOnlyRepairPolicy();

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
        new ValidationResult(),
        false,
    ],

    'warning' => [
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
