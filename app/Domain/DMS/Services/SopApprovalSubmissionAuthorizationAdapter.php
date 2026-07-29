<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Models\SopRole;
use App\Models\User;

class SopApprovalSubmissionAuthorizationAdapter implements ApprovalSubmissionAuthorization
{
    public function canSubmit(ApprovableSubject $subject, User $user): bool
    {
        if (! $user->can('Submit:ControlledDocument') && ! $user->can('Update:ControlledDocument')) {
            return false;
        }

        if ($user->hasRole(SopRole::ADMINISTRATOR)) {
            return true;
        }

        if ($user->hasRole(SopRole::MAKER)) {
            if ($user->department_id !== null && $user->department_id !== $subject->approvalSubjectDepartmentId()) {
                return false;
            }

            return true;
        }

        return $subject->approvalSubjectCreatedById() === $user->id
            || $subject->approvalSubjectOwnerId() === $user->id;
    }
}
