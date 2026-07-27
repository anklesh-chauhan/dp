<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\VariableDataType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class VariableDataTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VariableDataType');
    }

    public function view(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('View:VariableDataType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VariableDataType');
    }

    public function update(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Update:VariableDataType');
    }

    public function delete(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Delete:VariableDataType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:VariableDataType');
    }

    public function restore(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Restore:VariableDataType');
    }

    public function forceDelete(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('ForceDelete:VariableDataType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VariableDataType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VariableDataType');
    }

    public function replicate(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Replicate:VariableDataType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VariableDataType');
    }

    public function approve(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Approve:VariableDataType');
    }

    public function submit(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Submit:VariableDataType');
    }

    public function review(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Review:VariableDataType');
    }

    public function publish(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Publish:VariableDataType');
    }

    public function unpublish(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Unpublish:VariableDataType');
    }

    public function revise(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Revise:VariableDataType');
    }

    public function archive(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Archive:VariableDataType');
    }

    public function unarchive(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Unarchive:VariableDataType');
    }

    public function markObsolete(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('MarkObsolete:VariableDataType');
    }

    public function completeRetention(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('CompleteRetention:VariableDataType');
    }

    public function destroy(AuthUser $authUser, VariableDataType $variableDataType): bool
    {
        return $authUser->can('Destroy:VariableDataType');
    }
}
