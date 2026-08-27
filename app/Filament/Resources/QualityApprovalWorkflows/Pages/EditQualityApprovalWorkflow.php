<?php

declare(strict_types=1);

namespace App\Filament\Resources\QualityApprovalWorkflows\Pages;

use App\Filament\Resources\QualityApprovalWorkflows\QualityApprovalWorkflowResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditQualityApprovalWorkflow extends EditRecord
{
    protected static string $resource = QualityApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
