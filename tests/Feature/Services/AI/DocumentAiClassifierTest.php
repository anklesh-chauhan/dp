<?php

declare(strict_types=1);

use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\DocumentAiClassifier;
use App\Services\AI\Routing\LLMManager;
use App\Services\AI\Routing\ProviderRegistry;
use App\Services\AI\Routing\ProviderRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AI\FakeLLMProvider;
use App\Services\AI\Contracts\AiExecutionRecorder;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('ai.routing.document_classification', [
        'fake',
    ]);

    config()->set('ai.routing.document_type_selection', [
        'fake',
    ]);

    $this->registry = new ProviderRegistry();

    $this->router = new ProviderRouter(
        $this->registry,
    );

    $this->recorder = Mockery::mock(AiExecutionRecorder::class);

    $this->recorder
        ->shouldIgnoreMissing();

    $this->manager = new LLMManager(
        $this->router,
        $this->recorder,
    );
});

/**
 * Create a Document Category.
 */
function createDocumentCategory(
    string $name,
    string $code,
): DocumentCategory {
    return DocumentCategory::query()->create([
        'name' => $name,
        'code' => $code,
    ]);
}

/**
 * Create a Document Type.
 */
function createDocumentType(
    DocumentCategory $category,
    string $name,
    string $code,
): DocumentType {
    return DocumentType::query()->create([
        'name' => $name,
        'code' => $code,
        'category_id' => $category->getKey(),
        'requires_sop_reference' => false,
        'is_issuable' => true,
    ]);
}

/**
 * Create a Regulation Tag.
 */
function createRegulationTag(
    string $name,
    string $code,
): RegulationTag {
    return RegulationTag::query()->create([
        'name' => $name,
        'code' => $code,
        'description' => "{$name} regulation tag",
    ]);
}

it('classifies a document category and document type', function (): void {
    $procedureCategory = createDocumentCategory(
        name: 'Procedure',
        code: 'PROCEDURE',
    );

    createDocumentCategory(
        name: 'Record',
        code: 'RECORD',
    );

    $sop = createDocumentType(
        category: $procedureCategory,
        name: 'Standard Operating Procedure',
        code: 'SOP',
    );

    createDocumentType(
        category: $procedureCategory,
        name: 'Work Instruction',
        code: 'WI',
    );

    $fake = (new FakeLLMProvider('fake'))
        ->willReturnSequence([
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),

            new LLMResponse(
                content: [
                    'document_type_code' => 'SOP',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),
        ]);

    $this->registry->register($fake);

    $classifier = new DocumentAiClassifier(
        $this->manager,
    );

    $result = $classifier->classify(
        name: 'Equipment Cleaning Procedure',
        description: 'Defines the approved process for cleaning manufacturing equipment.',
        departmentName: 'Production',
    );

    expect($result)
        ->toBe([
            'category_id' => $procedureCategory->getKey(),
            'document_type_id' => $sop->getKey(),
            'regulation_tag_ids' => [],
        ])
        ->and($fake->callCount)
        ->toBe(2);
});

it('derives regulation tags from the selected document type', function (): void {
    $procedureCategory = createDocumentCategory(
        name: 'Procedure',
        code: 'PROCEDURE',
    );

    createDocumentCategory(
        name: 'Record',
        code: 'RECORD',
    );

    $sop = createDocumentType(
        category: $procedureCategory,
        name: 'Standard Operating Procedure',
        code: 'SOP',
    );

    createDocumentType(
        category: $procedureCategory,
        name: 'Work Instruction',
        code: 'WI',
    );

    $gmp = createRegulationTag(
        name: 'Good Manufacturing Practice',
        code: 'GMP',
    );

    $fda = createRegulationTag(
        name: 'FDA',
        code: 'FDA',
    );

    $sop->regulationTags()->attach([
        $gmp->getKey(),
        $fda->getKey(),
    ]);

    $fake = (new FakeLLMProvider('fake'))
        ->willReturnSequence([
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),

            new LLMResponse(
                content: [
                    'document_type_code' => 'SOP',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),
        ]);

    $this->registry->register($fake);

    $classifier = new DocumentAiClassifier(
        $this->manager,
    );

    $result = $classifier->classify(
        name: 'Equipment Cleaning Procedure',
        description: 'Defines GMP cleaning requirements.',
        departmentName: 'Production',
    );

    expect($result['category_id'])
        ->toBe($procedureCategory->getKey())
        ->and($result['document_type_id'])
        ->toBe($sop->getKey())
        ->and($result['regulation_tag_ids'])
        ->toEqualCanonicalizing([
            $gmp->getKey(),
            $fda->getKey(),
        ]);
});

it('skips document type ai selection when the category contains only one document type', function (): void {
    $procedureCategory = createDocumentCategory(
        name: 'Procedure',
        code: 'PROCEDURE',
    );

    createDocumentCategory(
        name: 'Record',
        code: 'RECORD',
    );

    $sop = createDocumentType(
        category: $procedureCategory,
        name: 'Standard Operating Procedure',
        code: 'SOP',
    );

    $fake = (new FakeLLMProvider('fake'))
        ->willReturn(
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),
        );

    $this->registry->register($fake);

    $classifier = new DocumentAiClassifier(
        $this->manager,
    );

    $result = $classifier->classify(
        name: 'Equipment Cleaning Procedure',
        description: 'Defines the cleaning procedure.',
        departmentName: 'Production',
    );

    expect($result)
        ->toBe([
            'category_id' => $procedureCategory->getKey(),
            'document_type_id' => $sop->getKey(),
            'regulation_tag_ids' => [],
        ])
        ->and($fake->callCount)
        ->toBe(1);
});

it('returns an empty result when no document categories exist', function (): void {
    $classifier = new DocumentAiClassifier(
        $this->manager,
    );

    $result = $classifier->classify(
        name: 'Equipment Cleaning Procedure',
        description: 'Defines the cleaning procedure.',
        departmentName: 'Production',
    );

    expect($result)->toBe([
        'category_id' => null,
        'document_type_id' => null,
        'regulation_tag_ids' => [],
    ]);
});

it('returns an empty result when ai selects an invalid category code', function (): void {
    createDocumentCategory(
        name: 'Procedure',
        code: 'PROCEDURE',
    );

    createDocumentCategory(
        name: 'Record',
        code: 'RECORD',
    );

    $fake = (new FakeLLMProvider('fake'))
        ->willReturn(
            new LLMResponse(
                content: [
                    'category_code' => 'INVALID',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),
        );

    $this->registry->register($fake);

    $classifier = new DocumentAiClassifier(
        $this->manager,
    );

    $result = $classifier->classify(
        name: 'Unknown Document',
        description: 'Unknown document purpose.',
        departmentName: 'Production',
    );

    expect($result)->toBe([
        'category_id' => null,
        'document_type_id' => null,
        'regulation_tag_ids' => [],
    ]);
});

it('returns an empty result when the selected category has no document types', function (): void {
    createDocumentCategory(
        name: 'Procedure',
        code: 'PROCEDURE',
    );

    createDocumentCategory(
        name: 'Record',
        code: 'RECORD',
    );

    $fake = (new FakeLLMProvider('fake'))
        ->willReturn(
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),
        );

    $this->registry->register($fake);

    $classifier = new DocumentAiClassifier(
        $this->manager,
    );

    $result = $classifier->classify(
        name: 'Equipment Cleaning Procedure',
        description: 'Defines the cleaning procedure.',
        departmentName: 'Production',
    );

    expect($result)->toBe([
        'category_id' => null,
        'document_type_id' => null,
        'regulation_tag_ids' => [],
    ]);
});

it('returns an empty result when ai selects an invalid document type code', function (): void {
    $procedureCategory = createDocumentCategory(
        name: 'Procedure',
        code: 'PROCEDURE',
    );

    createDocumentCategory(
        name: 'Record',
        code: 'RECORD',
    );

    createDocumentType(
        category: $procedureCategory,
        name: 'Standard Operating Procedure',
        code: 'SOP',
    );

    createDocumentType(
        category: $procedureCategory,
        name: 'Work Instruction',
        code: 'WI',
    );

    $fake = (new FakeLLMProvider('fake'))
        ->willReturnSequence([
            new LLMResponse(
                content: [
                    'category_code' => 'PROCEDURE',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),

            new LLMResponse(
                content: [
                    'document_type_code' => 'INVALID',
                ],
                provider: 'fake',
                model: 'fake-model',
            ),
        ]);

    $this->registry->register($fake);

    $classifier = new DocumentAiClassifier(
        $this->manager,
    );

    $result = $classifier->classify(
        name: 'Equipment Cleaning Procedure',
        description: 'Defines the cleaning procedure.',
        departmentName: 'Production',
    );

    expect($result)->toBe([
        'category_id' => null,
        'document_type_id' => null,
        'regulation_tag_ids' => [],
    ]);
});

it('returns an empty result when all llm providers fail', function (): void {
    createDocumentCategory(
        name: 'Procedure',
        code: 'PROCEDURE',
    );

    createDocumentCategory(
        name: 'Record',
        code: 'RECORD',
    );

    $fake = (new FakeLLMProvider('fake'))
        ->willFail('Provider unavailable.');

    $this->registry->register($fake);

    $classifier = new DocumentAiClassifier(
        $this->manager,
    );

    $result = $classifier->classify(
        name: 'Equipment Cleaning Procedure',
        description: 'Defines the cleaning procedure.',
        departmentName: 'Production',
    );

    expect($result)->toBe([
        'category_id' => null,
        'document_type_id' => null,
        'regulation_tag_ids' => [],
    ]);
});
