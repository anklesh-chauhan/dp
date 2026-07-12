<?php

declare(strict_types=1);

use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\KnowledgeGuide;
use App\Models\RegulationTag;
use App\Services\AI\DocumentAiClassifier;
use App\Services\AI\LLMServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes the published classification knowledge guide in the ai prompt', function (): void {
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::factory()->create([
        'category_id' => $category->id,
    ]);

    KnowledgeGuide::factory()->create([
        'slug' => KnowledgeGuide::ClassificationSlug,
        'title' => 'Document Classification Guide',
        'content' => '# Classification Guide\n\nUse the Quick-Reference Cross-Mapping Matrix.',
        'is_published' => true,
    ]);

    $capturedPrompt = null;

    $llmService = Mockery::mock(LLMServiceInterface::class);
    $llmService->shouldReceive('generateStructured')
        ->once()
        ->with(
            Mockery::on(function (string $prompt) use (&$capturedPrompt): bool {
                $capturedPrompt = $prompt;

                return str_contains($prompt, 'AUTHORITATIVE CLASSIFICATION REFERENCE')
                    && str_contains($prompt, 'Quick-Reference Cross-Mapping Matrix')
                    && str_contains($prompt, 'Calibration Log Template');
            }),
            Mockery::type('array'),
        )
        ->andReturn(['document_type_id' => $documentType->id]);

    app()->instance(LLMServiceInterface::class, $llmService);

    $result = app(DocumentAiClassifier::class)->classify(
        name: 'Calibration Log Template',
        description: 'Template for recording equipment calibration activities.',
        departmentName: 'Quality Assurance',
    );

    expect($capturedPrompt)->not->toBeNull()
        ->and($result['document_type_id'])->toBe($documentType->id)
        ->and($result['category_id'])->toBe($category->id);
});

it('omits the knowledge guide section when the classification guide is unpublished', function (): void {
    $documentType = DocumentType::factory()->create();

    KnowledgeGuide::factory()->unpublished()->create([
        'slug' => KnowledgeGuide::ClassificationSlug,
        'content' => 'Unpublished classification rules.',
    ]);

    $llmService = Mockery::mock(LLMServiceInterface::class);
    $llmService->shouldReceive('generateStructured')
        ->once()
        ->with(
            Mockery::on(fn (string $prompt): bool => ! str_contains($prompt, 'AUTHORITATIVE CLASSIFICATION REFERENCE')
                && ! str_contains($prompt, 'Unpublished classification rules.')),
            Mockery::type('array'),
        )
        ->andReturn(['document_type_id' => $documentType->id]);

    app()->instance(LLMServiceInterface::class, $llmService);

    app(DocumentAiClassifier::class)->classify(
        name: 'GMP Training SOP',
        description: 'Procedure for GMP training delivery.',
        departmentName: 'Human Resources',
    );
});

it('returns regulation tags from the classified document type', function (): void {
    $documentType = DocumentType::factory()->create();
    $tag = RegulationTag::query()->create([
        'name' => 'WHO GMP',
        'code' => 'WHO_GMP',
    ]);
    $documentType->regulationTags()->attach($tag);

    $llmService = Mockery::mock(LLMServiceInterface::class);
    $llmService->shouldReceive('generateStructured')
        ->once()
        ->andReturn(['document_type_id' => $documentType->id]);

    app()->instance(LLMServiceInterface::class, $llmService);

    $result = app(DocumentAiClassifier::class)->classify(
        name: 'Deviation Management SOP',
        description: 'Procedure for managing deviations.',
        departmentName: 'Quality Assurance',
    );

    expect($result['regulation_tag_ids'])->toBe([$tag->id]);
});
