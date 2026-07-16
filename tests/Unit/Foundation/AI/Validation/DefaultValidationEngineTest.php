<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\DefaultValidationEngine;
use App\Foundation\AI\Validation\Pipeline\RuleExecutor;
use App\Foundation\AI\Validation\Pipeline\ValidationPipeline;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;
use Tests\Unit\Fakes\Foundation\AI\Validation\PassingValidationRule;
use Tests\Unit\Fakes\Foundation\AI\Validation\FailingValidationRule;
use App\Foundation\AI\Validation\Exceptions\RuleExecutionException;
use Tests\Unit\Fakes\Foundation\AI\Validation\ExplodingValidationRule;

it('returns a passing validation result', function () {
    $engine = new DefaultValidationEngine(
        new ValidationPipeline(
            new RuleExecutor(),
        ),
    );

    $rules = new ValidationRuleCollection([
        new PassingValidationRule(),
    ]);

    $result = $engine->validate(
        artifact: new stdClass(),
        context: new ValidationContext(
            artifactType: 'test',
        ),
        rules: $rules,
    );

    expect($result)
        ->toBeInstanceOf(ValidationResult::class)
        ->and($result->passed())
        ->toBeTrue()
        ->and($result->failed())
        ->toBeFalse();
});

it('returns a failing validation result', function () {
    $engine = new DefaultValidationEngine(
        new ValidationPipeline(
            new RuleExecutor(),
        ),
    );

    $rules = new ValidationRuleCollection([
        new FailingValidationRule(),
    ]);

    $result = $engine->validate(
        artifact: new stdClass(),
        context: new ValidationContext(
            artifactType: 'test',
        ),
        rules: $rules,
    );

    expect($result->failed())
        ->toBeTrue()
        ->and($result->passed())
        ->toBeFalse()
        ->and($result->hasErrors())
        ->toBeTrue();
});

it('preserves validation issues', function () {
    $engine = new DefaultValidationEngine(
        new ValidationPipeline(
            new RuleExecutor(),
        ),
    );

    $rules = new ValidationRuleCollection([
        new FailingValidationRule(),
    ]);

    $result = $engine->validate(
        artifact: new stdClass(),
        context: new ValidationContext(
            artifactType: 'test',
        ),
        rules: $rules,
    );

    expect($result->issues())
        ->toHaveCount(1)
        ->and($result->issues()->isNotEmpty())
        ->toBeTrue();
});

it('propagates rule execution exceptions', function () {
    $engine = new DefaultValidationEngine(
        new ValidationPipeline(
            new RuleExecutor(),
        ),
    );

    $rules = new ValidationRuleCollection([
        new ExplodingValidationRule(),
    ]);

    expect(fn () => $engine->validate(
        artifact: new stdClass(),
        context: new ValidationContext(
            artifactType: 'test',
        ),
        rules: $rules,
    ))->toThrow(RuleExecutionException::class);
});
