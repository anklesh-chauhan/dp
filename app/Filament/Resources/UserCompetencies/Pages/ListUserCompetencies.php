<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserCompetencies\Pages;

use App\Filament\Resources\UserCompetencies\UserCompetencyResource;
use Filament\Resources\Pages\ListRecords;

final class ListUserCompetencies extends ListRecords
{
    protected static string $resource = UserCompetencyResource::class;
}
