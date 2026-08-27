<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\QualityApprovalWorkflow;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class QualityApprovalWorkflowPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QualityApprovalWorkflow');
    }

    public function view(AuthUser $authUser, QualityApprovalWorkflow $qualityApprovalWorkflow): bool
    {
        return $authUser->can('View:QualityApprovalWorkflow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QualityApprovalWorkflow');
    }

    public function update(AuthUser $authUser, QualityApprovalWorkflow $qualityApprovalWorkflow): bool
    {
        return $authUser->can('Update:QualityApprovalWorkflow');
    }

    public function delete(AuthUser $authUser, QualityApprovalWorkflow $qualityApprovalWorkflow): bool
    {
        return $authUser->can('Delete:QualityApprovalWorkflow')
            && $qualityApprovalWorkflow->approvalInstances()->doesntExist();
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QualityApprovalWorkflow');
    }

    public function restore(AuthUser $authUser, QualityApprovalWorkflow $qualityApprovalWorkflow): bool
    {
        return $authUser->can('Restore:QualityApprovalWorkflow');
    }

    public function forceDelete(AuthUser $authUser, QualityApprovalWorkflow $qualityApprovalWorkflow): bool
    {
        return $authUser->can('ForceDelete:QualityApprovalWorkflow')
            && $qualityApprovalWorkflow->approvalInstances()->doesntExist();
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QualityApprovalWorkflow');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QualityApprovalWorkflow');
    }

    public function replicate(AuthUser $authUser, QualityApprovalWorkflow $qualityApprovalWorkflow): bool
    {
        return $authUser->can('Replicate:QualityApprovalWorkflow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QualityApprovalWorkflow');
    }
}
