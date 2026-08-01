<?php

namespace App\Domain\DMS\Services;

use App\Models\ControlledDocument;
use App\Models\ControlledDocumentAccessGrant;
use App\Models\User;

class ControlledDocumentAccessService
{
    public function __construct(private PdfAccessPolicyService $policyService) {}

    public function canView(User $user, ControlledDocument $document): bool
    {
        if (! $user->can('ViewPdf:ControlledDocument') || ! $user->can('view', $document)) {
            return false;
        }

        if ($this->hasOwnerOrManagerAccess($user, $document)) {
            return true;
        }

        if ($document->accessGrants()->exists()) {
            return (bool) $this->activeGrant($user, $document)?->can_view;
        }

        return $this->policyService->decision($user, $document, PdfAccessPolicyService::ACTION_VIEW) ?? true;
    }

    public function canPrint(User $user, ControlledDocument $document): bool
    {
        if (! $user->can('PrintPdf:ControlledDocument') || ! $this->canView($user, $document)) {
            return false;
        }

        if ($this->hasOwnerOrManagerAccess($user, $document)) {
            return true;
        }

        if ($document->accessGrants()->exists()) {
            return (bool) $this->activeGrant($user, $document)?->can_print;
        }

        return $this->policyService->decision($user, $document, PdfAccessPolicyService::ACTION_PRINT) ?? true;
    }

    public function canDownload(User $user, ControlledDocument $document): bool
    {
        if (! $user->can('DownloadPdf:ControlledDocument') || ! $this->canView($user, $document)) {
            return false;
        }

        if ($this->hasOwnerOrManagerAccess($user, $document)) {
            return true;
        }

        if ($document->accessGrants()->exists()) {
            return (bool) $this->activeGrant($user, $document)?->can_download;
        }

        return $this->policyService->decision($user, $document, PdfAccessPolicyService::ACTION_DOWNLOAD) ?? true;
    }

    public function canManage(User $user, ControlledDocument $document): bool
    {
        return $user->can('ManagePdfAccess:ControlledDocument') && $user->can('view', $document);
    }

    private function hasOwnerOrManagerAccess(User $user, ControlledDocument $document): bool
    {
        return $document->owner_id === $user->id
            || $document->created_by === $user->id
            || $this->canManage($user, $document);
    }

    private function activeGrant(User $user, ControlledDocument $document): ?ControlledDocumentAccessGrant
    {
        return $document->accessGrants()
            ->where('user_id', $user->id)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
