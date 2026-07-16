<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\Exceptions\RuleExecutionException;
use App\Foundation\AI\Validation\Pipeline\RuleExecutor;
use App\Foundation\AI\Validation\Pipeline\ValidationPipeline;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use Tests\Unit\Fakes\Foundation\AI\Validation\ExplodingValidationRule;
use Tests\Unit\Fakes\Foundation\AI\Validation\FailingValidationRule;
use Tests\Unit\Fakes\Foundation\AI\Validation\PassingValidationRule;

it('returns an empty collection when all rules pass', function () {
    $pipeline = new ValidationPipeline(
        new RuleExecutor(),
    );

    $rules = new ValidationRuleCollection([
        new PassingValidationRule(),
        new PassingValidationRule(),
    ]);

    $result = $pipeline->execute(
        artifact: new stdClass(),
        context: new ValidationContext(
            artifactType: 'test',
        ),
        rules: $rules,
    );

    expect($result)
        ->toBeInstanceOf(ValidationIssueCollection::class)
        ->and($result->isEmpty())
        ->toBeTrue();
});

it('aggregates validation issues from multiple rules', function () {
    $pipeline = new ValidationPipeline(
        new RuleExecutor(),
    );

    $rules = new ValidationRuleCollection([
        new FailingValidationRule(),
        new FailingValidationRule(),
    ]);

    $result = $pipeline->execute(
        artifact: new stdClass(),
        context: new ValidationContext(
            artifactType: 'test',
        ),
        rules: $rules,
    );

    expect($result)
        ->toBeInstanceOf(ValidationIssueCollection::class)
        ->and($result)
        ->toHaveCount(2)
        ->and($result->isNotEmpty())
        ->toBeTrue();
});

it('returns an empty collection when there are no validation rules', function () {
    $pipeline = new ValidationPipeline(
        new RuleExecutor(),
    );

    $rules = new ValidationRuleCollection();

    $result = $pipeline->execute(
        artifact: new stdClass(),
        context: new ValidationContext(
            artifactType: 'test',
        ),
        rules: $rules,
    );

    expect($result)
        ->toBeInstanceOf(ValidationIssueCollection::class)
        ->and($result->isEmpty())
        ->toBeTrue()
        ->and($result)
        ->toHaveCount(0);
});

it('propagates rule execution exceptions', function () {
    $pipeline = new ValidationPipeline(
        new RuleExecutor(),
    );

    $rules = new ValidationRuleCollection([
        new ExplodingValidationRule(),
    ]);

    expect(fn () => $pipeline->execute(
        artifact: new stdClass(),
        context: new ValidationContext(
            artifactType: 'test',
        ),
        rules: $rules,
    ))->toThrow(RuleExecutionException::class);
});
