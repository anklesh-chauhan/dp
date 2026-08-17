<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\Pages;

use App\Filament\Resources\CompetencyCurricula\CompetencyCurriculumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCompetencyCurricula extends ListRecords
{
    protected static string $resource = CompetencyCurriculumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
