<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KnowledgeGuide;
use Illuminate\Auth\Access\HandlesAuthorization;

class KnowledgeGuidePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KnowledgeGuide');
    }

    public function view(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('View:KnowledgeGuide');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KnowledgeGuide');
    }

    public function update(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Update:KnowledgeGuide');
    }

    public function delete(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Delete:KnowledgeGuide');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KnowledgeGuide');
    }

    public function restore(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Restore:KnowledgeGuide');
    }

    public function forceDelete(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('ForceDelete:KnowledgeGuide');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KnowledgeGuide');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KnowledgeGuide');
    }

    public function replicate(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Replicate:KnowledgeGuide');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KnowledgeGuide');
    }

    public function approve(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Approve:KnowledgeGuide');
    }

    public function submit(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Submit:KnowledgeGuide');
    }

    public function review(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Review:KnowledgeGuide');
    }

    public function publish(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Publish:KnowledgeGuide');
    }

    public function unpublish(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Unpublish:KnowledgeGuide');
    }

    public function archive(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Archive:KnowledgeGuide');
    }

    public function unarchive(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Unarchive:KnowledgeGuide');
    }

    public function markObsolete(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('MarkObsolete:KnowledgeGuide');
    }

    public function completeRetention(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('CompleteRetention:KnowledgeGuide');
    }

    public function destroy(AuthUser $authUser, KnowledgeGuide $knowledgeGuide): bool
    {
        return $authUser->can('Destroy:KnowledgeGuide');
    }

}