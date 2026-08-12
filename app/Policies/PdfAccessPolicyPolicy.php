<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PdfAccessPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;

class PdfAccessPolicyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PdfAccessPolicy');
    }

    public function view(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('View:PdfAccessPolicy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PdfAccessPolicy');
    }

    public function update(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Update:PdfAccessPolicy');
    }

    public function delete(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Delete:PdfAccessPolicy');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PdfAccessPolicy');
    }

    public function restore(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Restore:PdfAccessPolicy');
    }

    public function forceDelete(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('ForceDelete:PdfAccessPolicy');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PdfAccessPolicy');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PdfAccessPolicy');
    }

    public function replicate(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Replicate:PdfAccessPolicy');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PdfAccessPolicy');
    }

    public function approve(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Approve:PdfAccessPolicy');
    }

    public function submit(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Submit:PdfAccessPolicy');
    }

    public function review(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Review:PdfAccessPolicy');
    }

    public function publish(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Publish:PdfAccessPolicy');
    }

    public function unpublish(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Unpublish:PdfAccessPolicy');
    }

    public function revise(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Revise:PdfAccessPolicy');
    }

    public function archive(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Archive:PdfAccessPolicy');
    }

    public function unarchive(AuthUser $authUser, PdfAccessPolicy $pdfAccessPolicy): bool
    {
        return $authUser->can('Unarchive:PdfAccessPolicy');
    }

}