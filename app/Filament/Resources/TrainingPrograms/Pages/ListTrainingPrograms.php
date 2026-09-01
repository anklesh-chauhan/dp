<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingPrograms\Pages;

use App\Filament\Resources\TrainingPrograms\TrainingProgramResource;
use Filament\Resources\Pages\ListRecords;

final class ListTrainingPrograms extends ListRecords
{
    protected static string $resource = TrainingProgramResource::class;
}
