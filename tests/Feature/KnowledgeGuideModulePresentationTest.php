<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Filament\Resources\KnowledgeGuides\KnowledgeGuideResource;
use App\Models\KnowledgeGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('defaults existing-style knowledge content to DMS ownership', function (): void {
    $guideId = DB::table('knowledge_guides')->insertGetId([
        'title' => 'Document classification',
        'slug' => 'document-classification',
        'content' => '# Guide',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(KnowledgeGuide::query()->findOrFail($guideId)->product_module)
        ->toBe(ProductModule::DMS);
});

it('only queries knowledge guides owned by enabled modules', function (): void {
    $dmsGuide = KnowledgeGuide::factory()->create([
        'product_module' => ProductModule::DMS,
    ]);
    $qmsGuide = KnowledgeGuide::factory()->create([
        'product_module' => ProductModule::QMS,
    ]);

    config()->set('modules.enabled', ['dms']);

    expect(KnowledgeGuideResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$dmsGuide->id])
        ->not->toContain($qmsGuide->id);

    config()->set('modules.enabled', ['dms', 'qms']);

    expect(KnowledgeGuideResource::getEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$dmsGuide->id, $qmsGuide->id]);
});

it('offers only enabled modules when knowledge content is managed', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(KnowledgeGuideResource::enabledModuleOptions())->toBe([
        ProductModule::DMS->value => ProductModule::DMS->label(),
    ]);

    config()->set('modules.enabled', ['dms', 'qms']);

    expect(KnowledgeGuideResource::enabledModuleOptions())->toBe([
        ProductModule::DMS->value => ProductModule::DMS->label(),
        ProductModule::QMS->value => ProductModule::QMS->label(),
    ]);
});
