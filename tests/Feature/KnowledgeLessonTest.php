<?php

declare(strict_types=1);

use App\Domain\DMS\Services\KnowledgeLessonService;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Deviation;
use App\Filament\Resources\KnowledgeLessons\KnowledgeLessonResource;
use App\Models\KnowledgeLesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the knowledge lesson schema', function (): void {
    expect(Schema::hasColumns('knowledge_lessons', [
        'lesson_number',
        'title',
        'summary',
        'body',
        'source_type',
        'source_id',
        'created_by',
        'is_published',
    ]))->toBeTrue()
        ->and(KnowledgeLessonResource::getNavigationGroup())->toBe('DMS · Help & Knowledge');
});

it('captures a lesson learned from a CAPA record', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $actor = User::factory()->create();
    $capa = Capa::factory()->create([
        'deviation_id' => Deviation::factory(),
        'title' => 'Replace failed gasket and revise PM.',
    ]);

    $lesson = app(KnowledgeLessonService::class)->recordFrom(
        $capa,
        $actor,
        'Gasket material was unsuitable for steam.',
        'Steam cycles degraded the original elastomer.',
        'Switch to the specified steam-resistant grade and train technicians.',
    );

    expect($lesson)->toBeInstanceOf(KnowledgeLesson::class)
        ->and($lesson->lesson_number)->toStartWith('KL-')
        ->and($lesson->source_id)->toBe($capa->getKey())
        ->and($lesson->created_by)->toBe($actor->id)
        ->and($lesson->is_published)->toBeTrue();
});
