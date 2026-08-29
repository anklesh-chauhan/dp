<?php

declare(strict_types=1);

use Anklesh\AiPlatform\Data\ValidationIssue;
use Anklesh\AiPlatform\Enums\ValidationSeverity;
use Anklesh\AiPlatform\Validation\OutputValidator;
use Anklesh\AiPlatform\Validation\Rules\RequiredPathRule;

it('passes when every required output path has a value', function () {
    $result = (new OutputValidator)->validate(
        ['customer' => ['name' => 'Taylor']],
        [new RequiredPathRule('customer.name')],
    );

    expect($result->passed())->toBeTrue()
        ->and($result->issues)->toBeEmpty();
});

it('returns an error for a missing required output path', function () {
    $result = (new OutputValidator)->validate(
        ['customer' => []],
        [new RequiredPathRule('customer.name')],
    );

    expect($result->failed())->toBeTrue()
        ->and($result->issues)->toHaveCount(1)
        ->and($result->issues[0]->code)->toBe('required_path')
        ->and($result->issues[0]->path)->toBe('customer.name');
});

it('does not fail for warning-level issues', function () {
    $result = (new OutputValidator)->validate(
        [],
        [new RequiredPathRule('summary', ValidationSeverity::Warning)],
    );

    expect($result->passed())->toBeTrue()
        ->and($result->issues)->toEqual([
            new ValidationIssue(
                code: 'required_path',
                message: 'The generated output must contain a value at [summary].',
                path: 'summary',
                severity: ValidationSeverity::Warning,
            ),
        ]);
});
