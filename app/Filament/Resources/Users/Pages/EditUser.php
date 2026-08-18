<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Shared\Services\UserAccessService;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $actor = auth()->user();
        $record = $this->record;

        if ($actor instanceof User && $record instanceof User) {
            app(UserAccessService::class)->recordRoleChange(
                $actor,
                $record,
                $record->roles()->pluck('name')->all(),
            );
        }
    }
}
