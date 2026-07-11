<?php

namespace App\Filament\Resources\KnowledgeGuides\Pages;

use App\Filament\Resources\KnowledgeGuides\KnowledgeGuideResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeGuide extends CreateRecord
{
    protected static string $resource = KnowledgeGuideResource::class;
}
