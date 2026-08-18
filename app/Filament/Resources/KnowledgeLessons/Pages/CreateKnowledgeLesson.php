<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeLessons\Pages;

use App\Filament\Resources\KnowledgeLessons\KnowledgeLessonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeLesson extends CreateRecord
{
    protected static string $resource = KnowledgeLessonResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
