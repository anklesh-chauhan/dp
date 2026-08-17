<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditFindings\Pages;

use App\Filament\Resources\AuditFindings\AuditFindingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAuditFindings extends ListRecords
{
    protected static string $resource = AuditFindingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
