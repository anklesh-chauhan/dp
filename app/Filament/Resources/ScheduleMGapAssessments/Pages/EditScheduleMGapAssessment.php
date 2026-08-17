<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments\Pages;

use App\Filament\Resources\ScheduleMGapAssessments\ScheduleMGapAssessmentResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditScheduleMGapAssessment extends EditRecord
{
    protected static string $resource = ScheduleMGapAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
