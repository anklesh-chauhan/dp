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

it('seeds published QMS Schedule M knowledge guides with module ownership', function (): void {
    KnowledgeGuide::factory()->create([
        'slug' => 'legacy-qms-gap-note',
        'title' => 'Legacy QMS gap note',
        'product_module' => ProductModule::QMS,
        'is_published' => true,
        'content' => '# Legacy',
    ]);

    app(KnowledgeGuideSeeder::class)->seedQmsGuides();

    $guides = KnowledgeGuide::query()
        ->where('product_module', ProductModule::QMS)
        ->published()
        ->ordered()
        ->get();

    expect($guides->pluck('slug')->all())->toBe([
        'qms-complaint-handling',
        'qms-self-inspection',
        'qms-product-quality-review',
        'qms-product-recall-and-returns',
        'qms-laboratory-oos',
        'qms-schedule-m-gap-assessment',
        'qms-computerized-system-operations',
    ])
        ->and($guides->every(fn (KnowledgeGuide $guide): bool => $guide->product_module === ProductModule::QMS))->toBeTrue()
        ->and($guides->firstWhere('slug', 'qms-complaint-handling')?->title)->toBe('Complaint Handling')
        ->and($guides->firstWhere('slug', 'qms-schedule-m-gap-assessment')?->content)
        ->toContain('Premises')
        ->toContain('Inspector Evidence Pack')
        ->and($guides->firstWhere('slug', 'qms-computerized-system-operations')?->content)
        ->toContain('integrity-checked')
        ->toContain('not certified')
        ->toContain('queue worker')
        ->toContain('Security Audit')
        ->not->toContain('The product does not run backups for you.')
        ->and(KnowledgeGuide::query()->where('slug', 'legacy-qms-gap-note')->value('is_published'))->toBeFalse();
});
