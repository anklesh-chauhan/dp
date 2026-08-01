<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\ControlledDocument;
use App\Models\PdfAccessPolicy;
use App\Models\User;

class PdfAccessPolicyService
{
    public const ACTION_VIEW = 'view';

    public const ACTION_PRINT = 'print';

    public const ACTION_DOWNLOAD = 'download';

    public function decision(User $user, ControlledDocument $document, string $action): ?bool
    {
        $roleIds = $user->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return null;
        }

        $policies = PdfAccessPolicy::query()
            ->currentlyActive()
            ->where($this->actionColumn($action), true)
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roleIds))
            ->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $document->department_id))
            ->where(fn ($query) => $query->whereNull('document_type_id')->orWhere('document_type_id', $document->document_type_id))
            ->where(fn ($query) => $query->whereNull('document_status_id')->orWhere('document_status_id', $document->document_status_id))
            ->orderByDesc('priority')
            ->orderByRaw("case when effect = 'deny' then 0 else 1 end")
            ->get();

        $highestPriority = $policies->first()?->priority;

        if ($highestPriority === null) {
            return null;
        }

        $applicable = $policies
            ->where('priority', $highestPriority)
            ->filter(fn (PdfAccessPolicy $policy): bool => $this->controlsAction($policy, $action));

        if ($applicable->isEmpty()) {
            return null;
        }

        if ($applicable->contains(fn (PdfAccessPolicy $policy): bool => $policy->effect === PdfAccessPolicy::EFFECT_DENY)) {
            return false;
        }

        return true;
    }

    private function controlsAction(PdfAccessPolicy $policy, string $action): bool
    {
        return match ($action) {
            self::ACTION_VIEW => $policy->can_view,
            self::ACTION_PRINT => $policy->can_print,
            self::ACTION_DOWNLOAD => $policy->can_download,
            default => false,
        };
    }

    private function actionColumn(string $action): string
    {
        return match ($action) {
            self::ACTION_VIEW => 'can_view',
            self::ACTION_PRINT => 'can_print',
            self::ACTION_DOWNLOAD => 'can_download',
            default => 'can_view',
        };
    }
}
