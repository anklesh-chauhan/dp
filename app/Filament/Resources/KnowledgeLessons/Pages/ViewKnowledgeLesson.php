<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeLessons\Pages;

use App\Filament\Resources\KnowledgeLessons\KnowledgeLessonResource;
use Filament\Resources\Pages\ViewRecord;

class ViewKnowledgeLesson extends ViewRecord
{
    protected static string $resource = KnowledgeLessonResource::class;
}
