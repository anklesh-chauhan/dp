<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeGuides\Pages;

use App\Filament\Resources\KnowledgeGuides\KnowledgeGuideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeGuides extends ListRecords
{
    protected static string $resource = KnowledgeGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => auth()->user()?->can('Create:KnowledgeGuide') ?? false),
        ];
    }
}
