<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\Pages;

use App\Filament\Resources\CompetencyCurricula\CompetencyCurriculumResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditCompetencyCurriculum extends EditRecord
{
    protected static string $resource = CompetencyCurriculumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
