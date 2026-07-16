<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

it('creates an informational validation issue', function (): void {
    $issue = ValidationIssueData::info(
        code: 'info-code',
        message: 'Information.',
    );

    expect($issue->code())->toBe('info-code')
        ->and($issue->message())->toBe('Information.')
        ->and($issue->severity())->toBe(ValidationSeverity::INFO)
        ->and($issue->path())->toBeNull()
        ->and($issue->metadata())->toBe([]);
});

it('creates a warning validation issue', function (): void {
    $issue = ValidationIssueData::warning(
        code: 'warning-code',
        message: 'Warning.',
        path: 'title',
    );

    expect($issue->severity())->toBe(ValidationSeverity::WARNING)
        ->and($issue->path())->toBe('title');
});

it('creates an error validation issue', function (): void {
    $issue = ValidationIssueData::error(
        code: 'error-code',
        message: 'Error.',
        path: 'sections.0.title',
        metadata: [
            'expected' => 'string',
        ],
    );

    expect($issue->severity())->toBe(ValidationSeverity::ERROR)
        ->and($issue->metadata())
        ->toBe([
            'expected' => 'string',
        ]);
});

it('creates a critical validation issue', function (): void {
    $issue = ValidationIssueData::critical(
        code: 'critical-code',
        message: 'Critical.',
    );

    expect($issue->severity())->toBe(ValidationSeverity::CRITICAL);
});

it('stores constructor values correctly', function (): void {
    $issue = new ValidationIssueData(
        code: 'duplicate-variable',
        message: 'Variable already exists.',
        severity: ValidationSeverity::WARNING,
        path: 'variables.2.name',
        metadata: [
            'variable' => 'Employee Name',
        ],
    );

    expect($issue->code())->toBe('duplicate-variable')
        ->and($issue->message())->toBe('Variable already exists.')
        ->and($issue->severity())->toBe(ValidationSeverity::WARNING)
        ->and($issue->path())->toBe('variables.2.name')
        ->and($issue->metadata())
        ->toBe([
            'variable' => 'Employee Name',
        ]);
});
