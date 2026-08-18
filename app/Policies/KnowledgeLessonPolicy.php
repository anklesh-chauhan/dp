<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgeLesson;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KnowledgeLessonPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KnowledgeLesson');
    }

    public function view(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('View:KnowledgeLesson');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KnowledgeLesson');
    }

    public function update(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Update:KnowledgeLesson');
    }

    public function delete(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Restore:KnowledgeLesson');
    }

    public function forceDelete(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KnowledgeLesson');
    }

    public function replicate(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Replicate:KnowledgeLesson');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KnowledgeLesson');
    }

    public function approve(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Approve:KnowledgeLesson');
    }

    public function submit(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Submit:KnowledgeLesson');
    }

    public function review(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Review:KnowledgeLesson');
    }

    public function publish(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Publish:KnowledgeLesson');
    }

    public function unpublish(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Unpublish:KnowledgeLesson');
    }

    public function revise(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Revise:KnowledgeLesson');
    }

    public function archive(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Archive:KnowledgeLesson');
    }

    public function unarchive(AuthUser $authUser, KnowledgeLesson $knowledgeLesson): bool
    {
        return $authUser->can('Unarchive:KnowledgeLesson');
    }
}
