<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\NumberSeries;
use Illuminate\Auth\Access\HandlesAuthorization;

class NumberSeriesPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NumberSeries');
    }

    public function view(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('View:NumberSeries');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NumberSeries');
    }

    public function update(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Update:NumberSeries');
    }

    public function delete(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Delete:NumberSeries');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NumberSeries');
    }

    public function restore(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Restore:NumberSeries');
    }

    public function forceDelete(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('ForceDelete:NumberSeries');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NumberSeries');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NumberSeries');
    }

    public function replicate(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Replicate:NumberSeries');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NumberSeries');
    }

    public function approve(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Approve:NumberSeries');
    }

    public function submit(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Submit:NumberSeries');
    }

    public function review(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Review:NumberSeries');
    }

    public function publish(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Publish:NumberSeries');
    }

    public function unpublish(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Unpublish:NumberSeries');
    }

    public function revise(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Revise:NumberSeries');
    }

    public function archive(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Archive:NumberSeries');
    }

    public function unarchive(AuthUser $authUser, NumberSeries $numberSeries): bool
    {
        return $authUser->can('Unarchive:NumberSeries');
    }

}