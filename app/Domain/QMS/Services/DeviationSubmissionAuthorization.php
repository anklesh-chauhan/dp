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
        if (! $user->can('Submit:Deviation') && ! $user->can('Update:Deviation')) {
            return false;
        }

        if ($user->can('Manage:Deviation')) {
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
