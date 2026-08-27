<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Models\User;

final class DeviationSubmissionAuthorization implements ApprovalSubmissionAuthorization
{
    public function canSubmit(ApprovableSubject $subject, User $user): bool
    {
        return $this->denialReason($subject, $user) === null;
    }

    public function denialReason(ApprovableSubject $subject, User $user): ?string
    {
        if (! $user->can('Submit:Deviation') && ! $user->can('Update:Deviation')) {
            return 'You do not have permission to submit this deviation.';
        }

        if ($user->can('Manage:Deviation')) {
            return null;
        }

        if (
            $user->department_id !== null
            && $user->department_id !== $subject->approvalSubjectDepartmentId()
        ) {
            return 'You can only submit deviations for your own department.';
        }

        if (
            $subject->approvalSubjectCreatedById() === $user->id
            || $subject->approvalSubjectOwnerId() === $user->id
        ) {
            return null;
        }

        return 'Only the reporter or owner can submit this deviation.';
    }
}
