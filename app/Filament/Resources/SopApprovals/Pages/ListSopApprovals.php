<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopApprovals\Pages;

use App\Filament\Resources\SopApprovals\SopApprovalResource;
use Filament\Resources\Pages\ListRecords;

class ListSopApprovals extends ListRecords
{
    protected static string $resource = SopApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
