<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

it('passes when there are no validation issues', function (): void {
    $result = new ValidationResult();

    expect($result->passed())->toBeTrue()
        ->and($result->failed())->toBeFalse()
        ->and($result->issues()->isEmpty())->toBeTrue()
        ->and($result->highestSeverity())->toBeNull();
});

it('passes when only informational and warning issues exist', function (): void {
    $result = new ValidationResult(
        new ValidationIssueCollection([
            ValidationIssueData::info('info', 'Information'),
            ValidationIssueData::warning('warning', 'Warning'),
        ]),
    );

    expect($result->passed())->toBeTrue()
        ->and($result->failed())->toBeFalse()
        ->and($result->hasWarnings())->toBeTrue()
        ->and($result->hasErrors())->toBeFalse()
        ->and($result->hasCriticalIssues())->toBeFalse();
});

it('fails when an error exists', function (): void {
    $result = new ValidationResult(
        new ValidationIssueCollection([
            ValidationIssueData::error('error', 'Error'),
        ]),
    );

    expect($result->passed())->toBeFalse()
        ->and($result->failed())->toBeTrue()
        ->and($result->hasErrors())->toBeTrue()
        ->and($result->highestSeverity())->toBe(ValidationSeverity::ERROR);
});

it('fails when a critical issue exists', function (): void {
    $result = new ValidationResult(
        new ValidationIssueCollection([
            ValidationIssueData::critical('critical', 'Critical'),
        ]),
    );

    expect($result->passed())->toBeFalse()
        ->and($result->failed())->toBeTrue()
        ->and($result->hasCriticalIssues())->toBeTrue()
        ->and($result->highestSeverity())->toBe(ValidationSeverity::CRITICAL);
});

it('returns the underlying validation issues', function (): void {
    $issues = new ValidationIssueCollection([
        ValidationIssueData::warning('warning', 'Warning'),
    ]);

    $result = new ValidationResult($issues);

    expect($result->issues())->toBe($issues);
});
