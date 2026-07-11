<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeGuides\Pages;

use App\Filament\Resources\KnowledgeGuides\KnowledgeGuideResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKnowledgeGuide extends ViewRecord
{
    protected static string $resource = KnowledgeGuideResource::class;

    protected string $view = 'filament.resources.knowledge-guides.view-knowledge-guide';

    public function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->can('Update:KnowledgeGuide') ?? false),
        ];
    }
}
