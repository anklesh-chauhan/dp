<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\Pages;

use App\Filament\Resources\ChangeControls\ChangeControlResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditChangeControl extends EditRecord
{
    protected static string $resource = ChangeControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
