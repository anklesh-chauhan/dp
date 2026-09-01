<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingAssignments\Pages;

use App\Filament\Resources\TrainingAssignments\TrainingAssignmentResource;
use Filament\Resources\Pages\ListRecords;

final class ListTrainingAssignments extends ListRecords
{
    protected static string $resource = TrainingAssignmentResource::class;
}
