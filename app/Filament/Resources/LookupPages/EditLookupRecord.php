<?php

declare(strict_types=1);

namespace App\Filament\Resources\LookupPages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLookupRecord extends EditRecord
{
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
