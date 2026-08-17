<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\Pages;

use App\Filament\Resources\CompetencyCurricula\CompetencyCurriculumResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewCompetencyCurriculum extends ViewRecord
{
    protected static string $resource = CompetencyCurriculumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
