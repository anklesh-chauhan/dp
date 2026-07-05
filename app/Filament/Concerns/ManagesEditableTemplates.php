<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\SopTemplate;
use Illuminate\Support\Facades\Auth;

trait ManagesEditableTemplates
{
    protected function canManageTemplateRecord(): bool
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof SopTemplate) {
            return true;
        }

        $user = Auth::user();

        return $user !== null && $owner->canBeEditedBy($user);
    }
}
