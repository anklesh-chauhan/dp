<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Rules\StringLengthRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('returns the rule code', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
    );

    expect($rule->code())
        ->toBe(StringLengthRule::CODE);
});

it('passes when the string length is within the configured range', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
        minimumLength: 3,
        maximumLength: 20,
    );

    $issues = $rule->validate(
        ['title' => 'QualiGxP'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('reports a string that is shorter than the minimum length', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
        minimumLength: 5,
    );

    $issues = $rule->validate(
        ['title' => 'ABC'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())->toBe('string_too_short')
        ->and($issues->all()[0]->path())->toBe('title');
});

it('reports a string that exceeds the maximum length', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
        maximumLength: 5,
    );

    $issues = $rule->validate(
        ['title' => 'Very Long Title'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())->toBe('string_too_long')
        ->and($issues->all()[0]->path())->toBe('title');
});

it('ignores missing fields', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
        minimumLength: 5,
    );

    expect(
        $rule->validate(
            [],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores null values', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
        minimumLength: 5,
    );

    expect(
        $rule->validate(
            ['title' => null],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores non-string values', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
        minimumLength: 5,
    );

    expect(
        $rule->validate(
            ['title' => 12345],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('supports minimum length only validation', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
        minimumLength: 5,
    );

    expect(
        $rule->validate(
            ['title' => 'QualiGxP'],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('supports maximum length only validation', function (): void {
    $rule = new StringLengthRule(
        new ArtifactAccessor,
        'title',
        maximumLength: 50,
    );

    expect(
        $rule->validate(
            ['title' => 'QualiGxP'],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('throws when the minimum length is negative', function (): void {
    expect(fn () => new StringLengthRule(
        new ArtifactAccessor,
        'title',
        minimumLength: -1,
    ))->toThrow(InvalidArgumentException::class);
});

it('throws when the maximum length is negative', function (): void {
    expect(fn () => new StringLengthRule(
        new ArtifactAccessor,
        'title',
        maximumLength: -1,
    ))->toThrow(InvalidArgumentException::class);
});

it('throws when the minimum length exceeds the maximum length', function (): void {
    expect(fn () => new StringLengthRule(
        new ArtifactAccessor,
        'title',
        minimumLength: 10,
        maximumLength: 5,
    ))->toThrow(InvalidArgumentException::class);
});
