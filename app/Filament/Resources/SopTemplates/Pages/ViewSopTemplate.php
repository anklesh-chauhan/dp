<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProcessesSopTemplateMetadataAi;
use App\Filament\Concerns\ProvidesRetentionLifecycleActions;
use App\Filament\Resources\SopTemplates\SopTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSopTemplate extends ViewRecord
{
    use HandlesServiceExceptions;
    use ProvidesRetentionLifecycleActions;
    use ProcessesSopTemplateMetadataAi;

    protected static string $resource = SopTemplateResource::class;

    protected function getActions(): array
    {
        return [
            ...$this->getTemplateRetentionLifecycleActions(),
            EditAction::make()
                ->visible(fn (): bool => Auth::user()?->can('update', $this->record) ?? false),
        ];
    }
}
