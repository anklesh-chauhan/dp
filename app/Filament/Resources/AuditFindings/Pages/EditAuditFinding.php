<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditFindings\Pages;

use App\Filament\Resources\AuditFindings\AuditFindingResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditAuditFinding extends EditRecord
{
    protected static string $resource = AuditFindingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
