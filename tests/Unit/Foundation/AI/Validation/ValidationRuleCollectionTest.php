<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

final readonly class FakeValidationRule implements ValidationRule
{
    public function code(): string
    {
        return 'fake-rule';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        return new ValidationIssueCollection();
    }
}

it('is empty by default', function (): void {
    $rules = new ValidationRuleCollection();

    expect($rules->isEmpty())->toBeTrue()
        ->and($rules->isNotEmpty())->toBeFalse()
        ->and($rules->count())->toBe(0);
});

it('counts validation rules', function (): void {
    $rules = new ValidationRuleCollection([
        new FakeValidationRule(),
        new FakeValidationRule(),
    ]);

    expect($rules->count())->toBe(2);
});

it('adds a rule immutably', function (): void {
    $original = new ValidationRuleCollection();

    $updated = $original->with(new FakeValidationRule());

    expect($original)->not->toBe($updated)
        ->and($original->count())->toBe(0)
        ->and($updated->count())->toBe(1);
});

it('is iterable', function (): void {
    $rules = new ValidationRuleCollection([
        new FakeValidationRule(),
        new FakeValidationRule(),
    ]);

    $count = 0;

    foreach ($rules as $rule) {
        expect($rule)->toBeInstanceOf(ValidationRule::class);

        $count++;
    }

    expect($count)->toBe(2);
});
