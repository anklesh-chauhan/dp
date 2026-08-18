<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\KnowledgeLesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class KnowledgeLessonService
{
    public function recordFrom(Model $source, User $actor, string $title, string $summary, string $body): KnowledgeLesson
    {
        return KnowledgeLesson::query()->create([
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'created_by' => $actor->getKey(),
            'is_published' => true,
        ]);
    }
}
