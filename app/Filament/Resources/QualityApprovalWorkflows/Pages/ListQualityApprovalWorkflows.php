<?php

declare(strict_types=1);

namespace App\Filament\Resources\QualityApprovalWorkflows\Pages;

use App\Filament\Resources\QualityApprovalWorkflows\QualityApprovalWorkflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListQualityApprovalWorkflows extends ListRecords
{
    protected static string $resource = QualityApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
