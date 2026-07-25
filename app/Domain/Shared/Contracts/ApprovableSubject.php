<?php

namespace App\Domain\Shared\Contracts;

interface ApprovableSubject
{
    public function approvalSubjectKey(): int|string|null;

    public function approvalSubjectReference(): string;

    public function approvalSubjectTitle(): string;

    public function approvalSubjectDepartmentId(): ?int;

    public function approvalSubjectCreatedById(): ?int;

    public function approvalSubjectOwnerId(): ?int;
}
