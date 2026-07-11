<?php

namespace App\Filament\Resources\KnowledgeGuides\Pages;

use App\Filament\Resources\KnowledgeGuides\KnowledgeGuideResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeGuide extends EditRecord
{
    protected static string $resource = KnowledgeGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
