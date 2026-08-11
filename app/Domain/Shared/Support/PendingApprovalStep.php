<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

final readonly class PendingApprovalStep
{
    public function __construct(
        public int $stepNo,
        public string $roleName,
        public ?string $stepTypeName = null,
        public ?string $departmentName = null,
    ) {}

    /**
     * Compact label for badges and subheadings, e.g. "Step 2 · Checker · Reviewer".
     */
    public function label(): string
    {
        $parts = ["Step {$this->stepNo}"];

        if ($this->stepTypeName !== null && $this->stepTypeName !== '') {
            $parts[] = $this->stepTypeName;
        }

        $parts[] = $this->roleName;

        if ($this->departmentName !== null && $this->departmentName !== '') {
            $parts[] = $this->departmentName;
        }

        return implode(' · ', $parts);
    }

    /**
     * Status badge text when the subject is under review.
     */
    public function withStatusLabel(string $statusLabel): string
    {
        return "{$statusLabel} · {$this->label()}";
    }
}
