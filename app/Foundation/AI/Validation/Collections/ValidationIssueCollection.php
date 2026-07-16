<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Collections;

use App\Foundation\AI\Validation\Contracts\ValidationIssue;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Immutable collection of validation issues.
 *
 * @implements IteratorAggregate<int, ValidationIssue>
 */
final readonly class ValidationIssueCollection implements Countable, IteratorAggregate
{
    /**
     * @param array<ValidationIssue> $issues
     */
    public function __construct(
        private array $issues = [],
    ) {
    }

    /**
     * Returns all validation issues.
     *
     * @return array<ValidationIssue>
     */
    public function all(): array
    {
        return $this->issues;
    }

    public function count(): int
    {
        return count($this->issues);
    }

    public function isEmpty(): bool
    {
        return $this->issues === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Determines whether the collection contains at least one issue with the
     * specified severity.
     */
    public function hasSeverity(ValidationSeverity $severity): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity() === $severity) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a new collection containing only issues of the given severity.
     */
    public function bySeverity(
        ValidationSeverity $severity,
    ): self {
        return new self(
            array_values(
                array_filter(
                    $this->issues,
                    static fn (ValidationIssue $issue): bool => $issue->severity() === $severity,
                ),
            ),
        );
    }

    /**
     * Determines whether the collection contains blocking issues.
     */
    public function containsBlockingIssues(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity()->isBlocking()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the highest severity present in the collection.
     */
    public function highestSeverity(): ?ValidationSeverity
    {
        $highest = null;

        foreach ($this->issues as $issue) {
            if (
                $highest === null ||
                $issue->severity()->priority() > $highest->priority()
            ) {
                $highest = $issue->severity();
            }
        }

        return $highest;
    }

    /**
     * Returns a new collection containing the supplied issue.
     */
    public function with(ValidationIssue $issue): self
    {
        return new self([
            ...$this->issues,
            $issue,
        ]);
    }

    /**
     * @return Traversable<int, ValidationIssue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->issues);
    }
}
