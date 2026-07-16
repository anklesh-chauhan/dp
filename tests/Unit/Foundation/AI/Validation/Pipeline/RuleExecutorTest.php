<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Pipeline\RuleExecutor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Exceptions\RuleExecutionException;
use Tests\Unit\Fakes\Foundation\AI\Validation\ExistingRuleExecutionExceptionRule;
use Tests\Unit\Fakes\Foundation\AI\Validation\ExplodingValidationRule;
use Tests\Unit\Fakes\Foundation\AI\Validation\PassingValidationRule;
use Tests\Unit\Fakes\Foundation\AI\Validation\FailingValidationRule;

it('executes a validation rule', function () {
    $executor = new RuleExecutor();

    $rule = new PassingValidationRule();

    $artifact = new stdClass();

    $context = new ValidationContext(
        artifactType: 'test',
    );

    $result = $executor->execute(
        $rule,
        $artifact,
        $context,
    );

    expect($result)
        ->toBeInstanceOf(ValidationIssueCollection::class)
        ->and($result->isEmpty())
        ->toBeTrue();
});

it('returns validation issues from the rule', function () {
    $executor = new RuleExecutor();

    $rule = new FailingValidationRule();

    $artifact = new stdClass();

    $context = new ValidationContext(
        artifactType: 'test',
    );

    $issues = $executor->execute(
        $rule,
        $artifact,
        $context,
    );

    expect($issues)
        ->toHaveCount(1)
        ->and($issues->isNotEmpty())
        ->toBeTrue();
});

it('wraps unexpected exceptions', function () {
    $executor = new RuleExecutor();

    $rule = new ExplodingValidationRule();

    $artifact = new stdClass();

    $context = new ValidationContext(
        artifactType: 'test',
    );

    expect(fn () => $executor->execute(
        $rule,
        $artifact,
        $context,
    ))->toThrow(RuleExecutionException::class);
});

it('preserves the previous exception', function () {
    $executor = new RuleExecutor();

    $rule = new ExplodingValidationRule();

    $artifact = new stdClass();

    $context = new ValidationContext(
        artifactType: 'test',
    );

    try {
        $executor->execute(
            $rule,
            $artifact,
            $context,
        );

        $this->fail('Expected RuleExecutionException to be thrown.');
    } catch (RuleExecutionException $exception) {
        expect($exception->getPrevious())
            ->toBeInstanceOf(RuntimeException::class)
            ->and($exception->getPrevious()?->getMessage())
            ->toBe('Boom!');
    }
});

it('does not wrap an existing rule execution exception', function () {
    $executor = new RuleExecutor();

    $rule = new ExistingRuleExecutionExceptionRule();

    $artifact = new stdClass();

    $context = new ValidationContext(
        artifactType: 'test',
    );

    try {
        $executor->execute(
            $rule,
            $artifact,
            $context,
        );

        $this->fail('Expected RuleExecutionException.');
    } catch (RuleExecutionException $exception) {
        expect($exception->getPrevious())
            ->toBeInstanceOf(RuntimeException::class)
            ->and($exception->getPrevious()?->getMessage())
            ->toBe('Already wrapped');
    }
});
