<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeLessons\Pages;

use App\Filament\Resources\KnowledgeLessons\KnowledgeLessonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeLessons extends ListRecords
{
    protected static string $resource = KnowledgeLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
