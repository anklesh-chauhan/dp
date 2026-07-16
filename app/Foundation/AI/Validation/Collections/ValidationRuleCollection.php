<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Collections;

use App\Foundation\AI\Validation\Contracts\ValidationRule;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Immutable collection of validation rules.
 *
 * @implements IteratorAggregate<int, ValidationRule>
 */
final readonly class ValidationRuleCollection implements Countable, IteratorAggregate
{
    /**
     * @param array<ValidationRule> $rules
     */
    public function __construct(
        private array $rules = [],
    ) {
    }

    /**
     * @return array<ValidationRule>
     */
    public function all(): array
    {
        return $this->rules;
    }

    public function count(): int
    {
        return count($this->rules);
    }

    public function isEmpty(): bool
    {
        return $this->rules === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Returns a new collection containing the supplied rule.
     */
    public function with(ValidationRule $rule): self
    {
        return new self([
            ...$this->rules,
            $rule,
        ]);
    }

    /**
     * @return Traversable<int, ValidationRule>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rules);
    }
}
