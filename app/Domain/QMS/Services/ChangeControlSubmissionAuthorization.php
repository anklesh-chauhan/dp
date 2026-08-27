<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Models\User;

final class ChangeControlSubmissionAuthorization implements ApprovalSubmissionAuthorization
{
    public function canSubmit(ApprovableSubject $subject, User $user): bool
    {
        return $this->denialReason($subject, $user) === null;
    }

    public function denialReason(ApprovableSubject $subject, User $user): ?string
    {
        if (! $user->can('Submit:ChangeControl') && ! $user->can('Update:ChangeControl')) {
            return 'You do not have permission to submit this change control.';
        }

        if ($user->can('Manage:ChangeControl')) {
            return null;
        }

        if (
            $user->department_id !== null
            && $user->department_id !== $subject->approvalSubjectDepartmentId()
        ) {
            return 'You can only submit change controls for your own department.';
        }

        if (
            $subject->approvalSubjectCreatedById() === $user->id
            || $subject->approvalSubjectOwnerId() === $user->id
        ) {
            return null;
        }

        return 'Only the requester or owner can submit this change control.';
    }
}
