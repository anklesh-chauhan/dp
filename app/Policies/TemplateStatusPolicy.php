<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TemplateStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class TemplateStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TemplateStatus');
    }

    public function view(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('View:TemplateStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TemplateStatus');
    }

    public function update(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Update:TemplateStatus');
    }

    public function delete(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Delete:TemplateStatus');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TemplateStatus');
    }

    public function restore(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Restore:TemplateStatus');
    }

    public function forceDelete(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('ForceDelete:TemplateStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TemplateStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TemplateStatus');
    }

    public function replicate(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Replicate:TemplateStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TemplateStatus');
    }

    public function approve(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Approve:TemplateStatus');
    }

    public function submit(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Submit:TemplateStatus');
    }

    public function review(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Review:TemplateStatus');
    }

    public function publish(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Publish:TemplateStatus');
    }

    public function unpublish(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Unpublish:TemplateStatus');
    }

    public function archive(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Archive:TemplateStatus');
    }

    public function unarchive(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Unarchive:TemplateStatus');
    }

    public function markObsolete(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('MarkObsolete:TemplateStatus');
    }

    public function completeRetention(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('CompleteRetention:TemplateStatus');
    }

    public function destroy(AuthUser $authUser, TemplateStatus $templateStatus): bool
    {
        return $authUser->can('Destroy:TemplateStatus');
    }

}