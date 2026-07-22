<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Rules\ArraySizeRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('returns the rule code', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
    );

    expect($rule->code())->toBe(ArraySizeRule::CODE);
});

it('passes when the array size is within the configured range', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        minimumCount: 1,
        maximumCount: 5,
    );

    $issues = $rule->validate(
        [
            'sections' => [
                ['title' => 'A'],
                ['title' => 'B'],
            ],
        ],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('reports an array that is too small', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        minimumCount: 2,
    );

    $issues = $rule->validate(
        [
            'sections' => [
                ['title' => 'Only One'],
            ],
        ],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())->toBe('array_too_small')
        ->and($issues->all()[0]->path())->toBe('sections');
});

it('reports an array that is too large', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        maximumCount: 2,
    );

    $issues = $rule->validate(
        [
            'sections' => [
                [],
                [],
                [],
            ],
        ],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())->toBe('array_too_large')
        ->and($issues->all()[0]->path())->toBe('sections');
});

it('ignores missing fields', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        minimumCount: 1,
    );

    expect(
        $rule->validate(
            [],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores null values', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        minimumCount: 1,
    );

    expect(
        $rule->validate(
            [
                'sections' => null,
            ],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores non-array values', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        minimumCount: 1,
    );

    expect(
        $rule->validate(
            [
                'sections' => 'not-an-array',
            ],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('supports minimum count only validation', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        minimumCount: 1,
    );

    expect(
        $rule->validate(
            [
                'sections' => [
                    [],
                    [],
                ],
            ],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('supports maximum count only validation', function (): void {
    $rule = new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        maximumCount: 5,
    );

    expect(
        $rule->validate(
            [
                'sections' => [
                    [],
                    [],
                ],
            ],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('throws when the minimum count is negative', function (): void {
    expect(fn () => new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        minimumCount: -1,
    ))->toThrow(\InvalidArgumentException::class);
});

it('throws when the maximum count is negative', function (): void {
    expect(fn () => new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        maximumCount: -1,
    ))->toThrow(\InvalidArgumentException::class);
});

it('throws when the minimum count exceeds the maximum count', function (): void {
    expect(fn () => new ArraySizeRule(
        new ArtifactAccessor(),
        'sections',
        minimumCount: 10,
        maximumCount: 5,
    ))->toThrow(\InvalidArgumentException::class);
});
