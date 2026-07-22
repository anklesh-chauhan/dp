<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Rules\UniqueValueRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('returns the rule code', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    expect($rule->code())
        ->toBe(UniqueValueRule::CODE);
});

it('passes when all values are unique', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    $issues = $rule->validate(
        [
            'sections' => [
                ['id' => 'intro'],
                ['id' => 'scope'],
                ['id' => 'approval'],
            ],
        ],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('reports duplicate string values', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    $issues = $rule->validate(
        [
            'sections' => [
                ['id' => 'intro'],
                ['id' => 'intro'],
            ],
        ],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())
        ->toBe('duplicate_value');
});

it('reports duplicate integer values', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    $issues = $rule->validate(
        [
            'sections' => [
                ['id' => 1],
                ['id' => 1],
            ],
        ],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toHaveCount(1);
});

it('uses strict comparison', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    $issues = $rule->validate(
        [
            'sections' => [
                ['id' => 1],
                ['id' => '1'],
            ],
        ],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('supports nested value fields', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'metadata.code',
    );

    $issues = $rule->validate(
        [
            'sections' => [
                ['metadata' => ['code' => 'A']],
                ['metadata' => ['code' => 'B']],
            ],
        ],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('ignores missing collections', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    expect(
        $rule->validate(
            [],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores null collections', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    expect(
        $rule->validate(
            ['sections' => null],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores non array collections', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    expect(
        $rule->validate(
            ['sections' => 'invalid'],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores non array items', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    expect(
        $rule->validate(
            [
                'sections' => [
                    'invalid',
                    ['id' => 'A'],
                ],
            ],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores missing value fields', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
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

it('ignores non scalar values', function (): void {
    $rule = new UniqueValueRule(
        new ArtifactAccessor(),
        'sections',
        'id',
    );

    expect(
        $rule->validate(
            [
                'sections' => [
                    ['id' => []],
                    ['id' => []],
                ],
            ],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});
