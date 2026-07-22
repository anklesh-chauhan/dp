<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Rules\RegexRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('returns the rule code', function (): void {
    $rule = new RegexRule(
        new ArtifactAccessor(),
        'code',
        '/^[A-Z]+$/',
    );

    expect($rule->code())
        ->toBe(RegexRule::CODE);
});

it('passes when the value matches the pattern', function (): void {
    $rule = new RegexRule(
        new ArtifactAccessor(),
        'code',
        '/^[A-Z]+$/',
    );

    expect(
        $rule->validate(
            ['code' => 'ABCDEF'],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('reports a mismatch', function (): void {
    $rule = new RegexRule(
        new ArtifactAccessor(),
        'code',
        '/^[A-Z]+$/',
    );

    $issues = $rule->validate(
        ['code' => 'abc123'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())
        ->toBe('regex_mismatch');
});

it('ignores missing fields', function (): void {
    $rule = new RegexRule(
        new ArtifactAccessor(),
        'code',
        '/^[A-Z]+$/',
    );

    expect(
        $rule->validate(
            [],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores null values', function (): void {
    $rule = new RegexRule(
        new ArtifactAccessor(),
        'code',
        '/^[A-Z]+$/',
    );

    expect(
        $rule->validate(
            ['code' => null],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('ignores non string values', function (): void {
    $rule = new RegexRule(
        new ArtifactAccessor(),
        'code',
        '/^[A-Z]+$/',
    );

    expect(
        $rule->validate(
            ['code' => 12345],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('supports complex regular expressions', function (): void {
    $rule = new RegexRule(
        new ArtifactAccessor(),
        'version',
        '/^\d+\.\d+\.\d+$/',
    );

    expect(
        $rule->validate(
            ['version' => '1.2.3'],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('throws when the pattern is invalid', function (): void {
    expect(fn () => new RegexRule(
        new ArtifactAccessor(),
        'code',
        '/(/',
    ))->toThrow(\InvalidArgumentException::class);
});
