<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\SopDocument;
use Illuminate\Support\Facades\Auth;

trait ManagesEditableDocuments
{
    protected function canManageDocumentRecord(): bool
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof SopDocument) {
            return true;
        }

        $user = Auth::user();

        return $user !== null && $owner->canBeEditedBy($user);
    }
}
