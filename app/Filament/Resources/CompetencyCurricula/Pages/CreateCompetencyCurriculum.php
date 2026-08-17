<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\Pages;

use App\Filament\Resources\CompetencyCurricula\CompetencyCurriculumResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCompetencyCurriculum extends CreateRecord
{
    protected static string $resource = CompetencyCurriculumResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
