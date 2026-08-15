<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Rules\EmptyValueRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('returns the rule code', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'title',
    );

    expect($rule->code())
        ->toBe(EmptyValueRule::CODE);
});

it('passes for a non empty string', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'title',
    );

    $issues = $rule->validate(
        ['title' => 'QualiGxP'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('reports a null value', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'title',
    );

    $issues = $rule->validate(
        ['title' => null],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->all()[0]->code())->toBe('empty_value')
        ->and($issues->all()[0]->path())->toBe('title');
});

it('reports an empty string', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'title',
    );

    $issues = $rule->validate(
        ['title' => ''],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toHaveCount(1);
});

it('reports a whitespace only string', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'title',
    );

    $issues = $rule->validate(
        ['title' => '     '],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toHaveCount(1);
});

it('reports an empty array', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'sections',
    );

    $issues = $rule->validate(
        ['sections' => []],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toHaveCount(1);
});

it('ignores missing fields', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'title',
    );

    $issues = $rule->validate(
        [],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('accepts false as a valid value', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'enabled',
    );

    $issues = $rule->validate(
        ['enabled' => false],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('accepts zero as a valid value', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'count',
    );

    $issues = $rule->validate(
        ['count' => 0],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});

it('accepts string zero as a valid value', function (): void {
    $rule = new EmptyValueRule(
        new ArtifactAccessor,
        'count',
    );

    $issues = $rule->validate(
        ['count' => '0'],
        new ValidationContext('generic_artifact'),
    );

    expect($issues)->toBeEmpty();
});
