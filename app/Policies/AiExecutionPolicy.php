<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AiExecution;
use Illuminate\Auth\Access\HandlesAuthorization;

class AiExecutionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AiExecution');
    }

    public function view(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('View:AiExecution');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AiExecution');
    }

    public function update(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Update:AiExecution');
    }

    public function delete(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Delete:AiExecution');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AiExecution');
    }

    public function restore(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Restore:AiExecution');
    }

    public function forceDelete(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('ForceDelete:AiExecution');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AiExecution');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AiExecution');
    }

    public function replicate(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Replicate:AiExecution');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AiExecution');
    }

    public function approve(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Approve:AiExecution');
    }

    public function submit(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Submit:AiExecution');
    }

    public function review(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Review:AiExecution');
    }

    public function publish(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Publish:AiExecution');
    }

    public function unpublish(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Unpublish:AiExecution');
    }

    public function revise(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Revise:AiExecution');
    }

    public function archive(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Archive:AiExecution');
    }

    public function unarchive(AuthUser $authUser, AiExecution $aiExecution): bool
    {
        return $authUser->can('Unarchive:AiExecution');
    }

}