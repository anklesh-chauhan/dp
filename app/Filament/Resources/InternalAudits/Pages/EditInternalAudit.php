<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalAudits\Pages;

use App\Filament\Resources\InternalAudits\InternalAuditResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditInternalAudit extends EditRecord
{
    protected static string $resource = InternalAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
