<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas\Pages;

use App\Filament\Resources\Capas\CapaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditCapa extends EditRecord
{
    protected static string $resource = CapaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
