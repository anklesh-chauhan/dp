<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Models\KnowledgeGuide;
use Database\Seeders\KnowledgeGuideSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds published DMS knowledge guides that match the application workflows', function (): void {
    KnowledgeGuide::factory()->create([
        'slug' => 'legacy-qms-note',
        'title' => 'Legacy QMS note',
        'product_module' => ProductModule::DMS,
        'is_published' => true,
        'content' => '# Legacy',
    ]);

    $this->seed(KnowledgeGuideSeeder::class);

    $guides = KnowledgeGuide::query()
        ->where('product_module', ProductModule::DMS)
        ->published()
        ->ordered()
        ->get();

    expect($guides->pluck('slug')->all())->toBe([
        'dms-overview',
        KnowledgeGuide::ClassificationSlug,
        'templates-and-approvals',
        'controlled-documents-lifecycle',
        'issuance-and-execution',
    ])
        ->and($guides->every(fn (KnowledgeGuide $guide): bool => $guide->product_module === ProductModule::DMS))->toBeTrue()
        ->and($guides->firstWhere('slug', 'dms-overview')?->title)->toBe('Getting Started with DMS')
        ->and($guides->firstWhere('slug', KnowledgeGuide::ClassificationSlug)?->title)->toBe('Document Types & Classification')
        ->and($guides->firstWhere('slug', KnowledgeGuide::ClassificationSlug)?->content)
        ->toContain('FORM')
        ->toContain('BMR')
        ->not->toContain('Quality Management System (QMS) document creation guide')
        ->and(KnowledgeGuide::query()->where('slug', 'legacy-qms-note')->value('is_published'))->toBeFalse();
});

it('keeps classification guide helper available after seeding', function (): void {
    $this->seed(KnowledgeGuideSeeder::class);

    expect(KnowledgeGuide::classificationGuide())
        ->not->toBeNull()
        ->slug->toBe(KnowledgeGuide::ClassificationSlug)
        ->product_module->toBe(ProductModule::DMS)
        ->is_published->toBeTrue();
});
