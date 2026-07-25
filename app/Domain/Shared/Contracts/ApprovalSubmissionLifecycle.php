<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Models\User;

interface ApprovalSubmissionLifecycle
{
    public function assertSubmittable(ApprovableSubject $subject): void;

    public function prepareSubmission(ApprovableSubject $subject, User $submitter): void;

    public function markSubmitted(
        ApprovableSubject $subject,
        ApprovalWorkflowDefinition $workflow,
        User $submitter,
    ): void;
}
