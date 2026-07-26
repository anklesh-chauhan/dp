<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\Pages;

use App\Filament\Resources\Deviations\DeviationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditDeviation extends EditRecord
{
    protected static string $resource = DeviationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
