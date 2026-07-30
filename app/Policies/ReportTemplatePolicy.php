<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReportTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReportTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReportTemplate');
    }

    public function view(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('View:ReportTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReportTemplate');
    }

    public function update(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Update:ReportTemplate');
    }

    public function delete(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Delete:ReportTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReportTemplate');
    }

    public function restore(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Restore:ReportTemplate');
    }

    public function forceDelete(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('ForceDelete:ReportTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReportTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReportTemplate');
    }

    public function replicate(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Replicate:ReportTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReportTemplate');
    }

    public function approve(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Approve:ReportTemplate');
    }

    public function submit(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Submit:ReportTemplate');
    }

    public function review(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Review:ReportTemplate');
    }

    public function publish(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Publish:ReportTemplate');
    }

    public function unpublish(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Unpublish:ReportTemplate');
    }

    public function revise(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Revise:ReportTemplate');
    }

    public function archive(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Archive:ReportTemplate');
    }

    public function unarchive(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Unarchive:ReportTemplate');
    }

    public function markObsolete(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('MarkObsolete:ReportTemplate');
    }

    public function completeRetention(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('CompleteRetention:ReportTemplate');
    }

    public function destroy(AuthUser $authUser, ReportTemplate $reportTemplate): bool
    {
        return $authUser->can('Destroy:ReportTemplate');
    }
}
