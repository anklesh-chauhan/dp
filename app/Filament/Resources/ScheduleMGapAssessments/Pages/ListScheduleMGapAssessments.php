<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments\Pages;

use App\Filament\Resources\ScheduleMGapAssessments\ScheduleMGapAssessmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListScheduleMGapAssessments extends ListRecords
{
    protected static string $resource = ScheduleMGapAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
