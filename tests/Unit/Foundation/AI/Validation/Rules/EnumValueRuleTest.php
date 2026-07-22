<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Rules\EnumValueRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('returns the rule code', function (): void {
    $rule = new EnumValueRule(
        new ArtifactAccessor(),
        'status',
        ['draft', 'approved'],
    );

    expect($rule->code())->toBe(EnumValueRule::CODE);
});

it('passes when the value is allowed', function (): void {
    $rule = new EnumValueRule(
        new ArtifactAccessor(),
        'status',
        ['draft', 'approved'],
    );

    $issues = $rule->validate(
        ['status' => 'draft'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('reports an invalid enum value', function (): void {
    $rule = new EnumValueRule(
        new ArtifactAccessor(),
        'status',
        ['draft', 'approved'],
    );

    $issues = $rule->validate(
        ['status' => 'archived'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())->toBe('invalid_enum_value')
        ->and($issues->all()[0]->path())->toBe('status');
});

it('ignores missing fields', function (): void {
    $rule = new EnumValueRule(
        new ArtifactAccessor(),
        'status',
        ['draft', 'approved'],
    );

    expect(
        $rule->validate(
            [],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores null values', function (): void {
    $rule = new EnumValueRule(
        new ArtifactAccessor(),
        'status',
        ['draft', 'approved'],
    );

    expect(
        $rule->validate(
            ['status' => null],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('supports integer values', function (): void {
    $rule = new EnumValueRule(
        new ArtifactAccessor(),
        'priority',
        [1, 2, 3],
    );

    expect(
        $rule->validate(
            ['priority' => 2],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('supports boolean values', function (): void {
    $rule = new EnumValueRule(
        new ArtifactAccessor(),
        'enabled',
        [true, false],
    );

    expect(
        $rule->validate(
            ['enabled' => false],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('uses strict comparison', function (): void {
    $rule = new EnumValueRule(
        new ArtifactAccessor(),
        'priority',
        [1],
    );

    $issues = $rule->validate(
        ['priority' => '1'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toHaveCount(1);
});

it('throws when allowed values are empty', function (): void {
    expect(fn () => new EnumValueRule(
        new ArtifactAccessor(),
        'status',
        [],
    ))->toThrow(\InvalidArgumentException::class);
});
