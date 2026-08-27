<?php

declare(strict_types=1);

namespace App\Filament\Resources\QualityApprovalWorkflows\Pages;

use App\Filament\Resources\QualityApprovalWorkflows\QualityApprovalWorkflowResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewQualityApprovalWorkflow extends ViewRecord
{
    protected static string $resource = QualityApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
