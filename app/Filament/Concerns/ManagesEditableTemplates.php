<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\Auth;

trait ManagesEditableTemplates
{
    public function isReadOnly(): bool
    {
        if ($this->canStartDraftRevision()) {
            return parent::isReadOnly();
        }

        if (! $this->canManageTemplateRecord()) {
            return true;
        }

        return parent::isReadOnly();
    }

    protected function canManageTemplateRecord(): bool
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof DocumentTemplate) {
            return true;
        }

        $user = Auth::user();

        return $user !== null && $owner->canBeEditedBy($user);
    }

    protected function canStartDraftRevision(): bool
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof DocumentTemplate) {
            return false;
        }

        $user = Auth::user();

        return $user !== null && $owner->canStartDraftRevisionBy($user);
    }
}
