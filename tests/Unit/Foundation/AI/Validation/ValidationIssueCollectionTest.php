<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

it('is empty by default', function (): void {
    $issues = new ValidationIssueCollection();

    expect($issues->isEmpty())->toBeTrue()
        ->and($issues->isNotEmpty())->toBeFalse()
        ->and($issues->count())->toBe(0);
});

it('counts validation issues', function (): void {
    $issues = new ValidationIssueCollection([
        ValidationIssueData::info('info', 'Info'),
        ValidationIssueData::warning('warning', 'Warning'),
    ]);

    expect($issues->count())->toBe(2)
        ->and($issues->isNotEmpty())->toBeTrue();
});

it('adds an issue immutably', function (): void {
    $original = new ValidationIssueCollection();

    $updated = $original->with(
        ValidationIssueData::error(
            'missing-title',
            'Title is required.',
        ),
    );

    expect($original)->not->toBe($updated)
        ->and($original->count())->toBe(0)
        ->and($updated->count())->toBe(1);
});

it('filters issues by severity', function (): void {
    $issues = new ValidationIssueCollection([
        ValidationIssueData::info('i1', 'Info'),
        ValidationIssueData::warning('w1', 'Warning'),
        ValidationIssueData::warning('w2', 'Warning'),
        ValidationIssueData::error('e1', 'Error'),
    ]);

    $warnings = $issues->bySeverity(ValidationSeverity::WARNING);

    expect($warnings)->toBeInstanceOf(ValidationIssueCollection::class)
        ->and($warnings->count())->toBe(2);
});

it('determines whether a severity exists', function (): void {
    $issues = new ValidationIssueCollection([
        ValidationIssueData::critical('critical', 'Critical'),
    ]);

    expect($issues->hasSeverity(ValidationSeverity::CRITICAL))->toBeTrue()
        ->and($issues->hasSeverity(ValidationSeverity::ERROR))->toBeFalse();
});

it('returns the highest severity', function (): void {
    $issues = new ValidationIssueCollection([
        ValidationIssueData::info('i', 'Info'),
        ValidationIssueData::warning('w', 'Warning'),
        ValidationIssueData::critical('c', 'Critical'),
        ValidationIssueData::error('e', 'Error'),
    ]);

    expect($issues->highestSeverity())
        ->toBe(ValidationSeverity::CRITICAL);
});

it('returns null when the collection is empty', function (): void {
    $issues = new ValidationIssueCollection();

    expect($issues->highestSeverity())->toBeNull();
});

it('detects blocking issues', function (): void {
    $issues = new ValidationIssueCollection([
        ValidationIssueData::warning('warning', 'Warning'),
        ValidationIssueData::error('error', 'Error'),
    ]);

    expect($issues->containsBlockingIssues())->toBeTrue();
});

it('does not detect blocking issues when only informational issues exist', function (): void {
    $issues = new ValidationIssueCollection([
        ValidationIssueData::info('info', 'Info'),
        ValidationIssueData::warning('warning', 'Warning'),
    ]);

    expect($issues->containsBlockingIssues())->toBeFalse();
});

it('is iterable', function (): void {
    $issues = new ValidationIssueCollection([
        ValidationIssueData::info('i1', 'Info'),
        ValidationIssueData::warning('w1', 'Warning'),
    ]);

    $count = 0;

    foreach ($issues as $issue) {
        expect($issue)->toBeInstanceOf(\App\Foundation\AI\Validation\Contracts\ValidationIssue::class);

        $count++;
    }

    expect($count)->toBe(2);
});
