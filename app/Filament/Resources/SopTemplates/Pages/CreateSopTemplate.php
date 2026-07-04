<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Filament\Resources\SopTemplates\SopTemplateResource;
use App\Models\TemplateStatus;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSopTemplate extends CreateRecord
{
    protected static string $resource = SopTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['template_status_id'] ??= TemplateStatus::idFor(TemplateStatus::DRAFT);

        return $data;
    }
}
