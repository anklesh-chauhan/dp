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
        if (! $user->can('Submit:ChangeControl') && ! $user->can('Update:ChangeControl')) {
            return false;
        }

        if ($user->can('Manage:ChangeControl')) {
            return true;
        }

        if (
            $user->department_id !== null
            && $user->department_id !== $subject->approvalSubjectDepartmentId()
        ) {
            return false;
        }

        return $subject->approvalSubjectCreatedById() === $user->id
            || $subject->approvalSubjectOwnerId() === $user->id;
    }
}
