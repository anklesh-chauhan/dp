<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Rules\CrossFieldRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('returns the rule code', function (): void {
    $rule = new CrossFieldRule(
        issueCode: 'test',
        message: 'Test message.',
        predicate: static fn (): bool => true,
    );

    expect($rule->code())
        ->toBe(CrossFieldRule::CODE);
});

it('passes when the predicate succeeds', function (): void {
    $rule = new CrossFieldRule(
        issueCode: 'invalid',
        message: 'Validation failed.',
        predicate: static fn (): bool => true,
    );

    $issues = $rule->validate(
        [],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('reports an issue when the predicate fails', function (): void {
    $rule = new CrossFieldRule(
        issueCode: 'invalid',
        message: 'Validation failed.',
        predicate: static fn (): bool => false,
        path: 'status',
    );

    $issues = $rule->validate(
        [],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())->toBe('invalid')
        ->and($issues->all()[0]->path())->toBe('status');
});

it('passes the artifact to the predicate', function (): void {
    $artifact = [
        'minimum' => 10,
        'maximum' => 20,
    ];

    $rule = new CrossFieldRule(
        issueCode: 'range',
        message: 'Invalid range.',
        predicate: static function (array $artifact): bool {
            return $artifact['minimum'] <= $artifact['maximum'];
        },
    );

    expect(
        $rule->validate(
            $artifact,
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});

it('passes the validation context to the predicate', function (): void {
    $rule = new CrossFieldRule(
        issueCode: 'context',
        message: 'Context validation failed.',
        predicate: static function (
            mixed $artifact,
            ValidationContext $context,
        ): bool {
            return $context->artifactType() === 'generic_artifact';
        },
    );

    expect(
        $rule->validate(
            [],
            new ValidationContext('generic_artifact'),
        ),
    )->toBeEmpty();
});
