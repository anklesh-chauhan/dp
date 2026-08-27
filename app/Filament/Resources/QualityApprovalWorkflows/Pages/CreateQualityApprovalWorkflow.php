<?php

declare(strict_types=1);

namespace App\Filament\Resources\QualityApprovalWorkflows\Pages;

use App\Filament\Resources\QualityApprovalWorkflows\QualityApprovalWorkflowResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateQualityApprovalWorkflow extends CreateRecord
{
    protected static string $resource = QualityApprovalWorkflowResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
