<?php

declare(strict_types=1);

namespace App\Filament\Resources\BatchReleases\Pages;

use App\Filament\Resources\BatchReleases\BatchReleaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListBatchReleases extends ListRecords
{
    protected static string $resource = BatchReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
