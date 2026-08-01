<?php

declare(strict_types=1);

use App\Filament\Resources\DocumentTemplates\Pages\CreateDocumentTemplate;
use App\Filament\Support\DocumentClassificationFormFields;
use App\Jobs\ProcessDocumentTemplateMetadataAiJob;
use App\Jobs\GenerateRegulatedTemplateJob;
use App\Models\AiTask;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\AI\Enums\AiTaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    Bus::fake();

    TemplateStatus::query()->create([
        'name' => 'Draft',
        'code' => TemplateStatus::DRAFT,
    ]);

    $this->user = User::factory()->create();

    actingAs($this->user);

    Gate::before(
        static fn (): bool => true,
    );
});

it('can mount the create document template page', function (): void {
    expect(auth()->check())
        ->toBeTrue()
        ->and(auth()->id())
        ->toBe($this->user->getKey());

    Livewire::test(CreateDocumentTemplate::class)
        ->assertSuccessful();
});

it('shows every regulation tag independently of the selected document type', function (): void {
    $documentType = DocumentType::factory()->create();
    $defaultTag = RegulationTag::query()->create([
        'name' => 'Good Manufacturing Practice',
        'code' => 'GMP',
    ]);
    $independentTag = RegulationTag::query()->create([
        'name' => 'Data Integrity',
        'code' => 'DATA_INTEGRITY',
    ]);

    $documentType->regulationTags()->attach($defaultTag);

    expect(DocumentClassificationFormFields::regulationTagOptions())
        ->toBe([
            $independentTag->getKey() => 'Data Integrity',
            $defaultTag->getKey() => 'Good Manufacturing Practice',
        ]);
});

it('persists a regulation tag that is not assigned to the selected document type', function (): void {
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::factory()->create();
    $regulationTag = RegulationTag::query()->create([
        'name' => 'Data Integrity',
        'code' => 'DATA_INTEGRITY',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => 'Data Integrity Procedure',
            'code' => 'TPL-DATA-INTEGRITY',
            'department_id' => $department->getKey(),
            'description' => 'Controls for attributable and accurate records.',
            'category_id' => $category->getKey(),
            'document_type_id' => $documentType->getKey(),
            'regulationTags' => [$regulationTag->getKey()],
            'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
            'current_version' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $template = DocumentTemplate::query()
        ->where('code', 'TPL-DATA-INTEGRITY')
        ->firstOrFail();

    expect($template->regulationTags()->pluck('regulation_tags.id')->all())
        ->toBe([$regulationTag->getKey()]);
});

it('does not generate the template with ai when using the regular create action', function (): void {
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::factory()->create();
    $regulationTag = RegulationTag::query()->create([
        'name' => 'Good Manufacturing Practice',
        'code' => 'GMP-MANUAL',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => 'Manual Template',
            'code' => 'TPL-MANUAL',
            'department_id' => $department->getKey(),
            'category_id' => $category->getKey(),
            'document_type_id' => $documentType->getKey(),
            'regulationTags' => [$regulationTag->getKey()],
            'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
            'current_version' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $template = DocumentTemplate::query()->where('code', 'TPL-MANUAL')->firstOrFail();

    expect($template->generation_status)->toBe('pending');
    Bus::assertNotDispatched(GenerateRegulatedTemplateJob::class);
});

it('generates the template with ai only when using the ai create action', function (): void {
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::factory()->create();
    $regulationTag = RegulationTag::query()->create([
        'name' => 'Good Manufacturing Practice',
        'code' => 'GMP-AI',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => 'AI Template',
            'code' => 'TPL-AI',
            'department_id' => $department->getKey(),
            'category_id' => $category->getKey(),
            'document_type_id' => $documentType->getKey(),
            'regulationTags' => [$regulationTag->getKey()],
            'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
            'current_version' => 0,
        ])
        ->call('createWithAi')
        ->assertHasNoFormErrors();

    $template = DocumentTemplate::query()->where('code', 'TPL-AI')->firstOrFail();

    expect($template->generation_status)->toBe(DocumentTemplate::GENERATION_STATUS_PROCESSING);
    Bus::assertDispatched(GenerateRegulatedTemplateJob::class);
});

it('creates an ai task and dispatches the metadata processing job', function (): void {
    $department = Department::factory()->create([
        'name' => 'Production',
    ]);

    $component = Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => 'Equipment Cleaning Procedure',
            'department_id' => $department->getKey(),
        ])
        ->call('startMetadataAiGeneration');

    $task = AiTask::query()->sole();

    expect($task->status)
        ->toBe(AiTaskStatus::PENDING);

    $component
        ->assertSet('metadataAiTaskId', $task->getKey())
        ->assertSet('metadataAiTaskPolling', true)
        ->assertSet('metadataAiProgress', 0);

    Bus::assertDispatched(
        ProcessDocumentTemplateMetadataAiJob::class,
        fn (ProcessDocumentTemplateMetadataAiJob $job): bool => $job->aiTaskId === $task->getKey(),
    );
});

it('stores the correct template metadata in the ai task input', function (): void {
    $department = Department::factory()->create([
        'name' => 'Quality Assurance',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => 'Deviation Management Procedure',
            'department_id' => $department->getKey(),
        ])
        ->call('startMetadataAiGeneration');

    $task = AiTask::query()->sole();

    expect($task->input)
        ->toBeArray()
        ->and($task->input['name'])
        ->toBe('Deviation Management Procedure')
        ->and($task->input['department_name'])
        ->toBe('Quality Assurance');

    Bus::assertDispatched(
        ProcessDocumentTemplateMetadataAiJob::class,
    );
});

it('prevents duplicate ai task dispatch while metadata generation is running', function (): void {
    $department = Department::factory()->create([
        'name' => 'Production',
    ]);

    $component = Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => 'Equipment Cleaning Procedure',
            'department_id' => $department->getKey(),
        ])
        ->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(1);

    Bus::assertDispatchedTimes(
        ProcessDocumentTemplateMetadataAiJob::class,
        1,
    );

    $component->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(1);

    Bus::assertDispatchedTimes(
        ProcessDocumentTemplateMetadataAiJob::class,
        1,
    );
});

it('does not create an ai task when the template name is missing', function (): void {
    $department = Department::factory()->create([
        'name' => 'Production',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => '',
            'department_id' => $department->getKey(),
        ])
        ->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(0);

    Bus::assertNotDispatched(
        ProcessDocumentTemplateMetadataAiJob::class,
    );
});

it('does not create an ai task when the department is missing', function (): void {
    Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => 'Equipment Cleaning Procedure',
            'department_id' => null,
        ])
        ->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(0);

    Bus::assertNotDispatched(
        ProcessDocumentTemplateMetadataAiJob::class,
    );
});

it('does not create an ai task when the selected department does not exist', function (): void {
    Livewire::test(CreateDocumentTemplate::class)
        ->fillForm([
            'name' => 'Equipment Cleaning Procedure',
            'department_id' => 999999,
        ])
        ->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(0);

    Bus::assertNotDispatched(
        ProcessDocumentTemplateMetadataAiJob::class,
    );
});

it('refreshes ai task progress while processing', function (): void {
    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::PROCESSING,
        'progress' => 65,
        'current_step' => 'Classifying document metadata',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiProgress', 65)
        ->assertSet(
            'metadataAiCurrentStep',
            'Classifying document metadata',
        )
        ->assertSet('metadataAiTaskPolling', true);
});

it('applies a completed ai task result to the sop template form', function (): void {
    $category = DocumentCategory::factory()->create([
        'name' => 'Procedures',
        'code' => 'PROCEDURE',
    ]);

    $documentType = DocumentType::factory()->create([
        'name' => 'Standard Operating Procedure',
        'code' => DocumentType::SOP,
    ]);

    $regulationTag = RegulationTag::query()->create([
        'name' => 'Good Manufacturing Practice',
        'code' => 'GMP',
        'description' => 'Good Manufacturing Practice requirements.',
    ]);

    $documentType
        ->regulationTags()
        ->attach($regulationTag);

    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::COMPLETED,
        'progress' => 100,
        'current_step' => 'Completed',
        'result' => [
            'description' => 'Defines the requirements and responsibilities for cleaning manufacturing equipment.',
            'description_reasoning' => 'The description reflects the operational and compliance requirements of the Production department.',
            'category_id' => $category->getKey(),
            'document_type_id' => $documentType->getKey(),
            'regulation_tag_ids' => [
                $regulationTag->getKey(),
            ],
        ],
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertFormSet([
            'description' => 'Defines the requirements and responsibilities for cleaning manufacturing equipment.',
            'category_id' => $category->getKey(),
            'document_type_id' => $documentType->getKey(),
            'regulationTags' => [
                $regulationTag->getKey(),
            ],
        ])
        ->assertSet('metadataAiProgress', 0)
        ->assertSet('metadataAiTaskPolling', false);
});

it('handles a failed ai task and stops polling', function (): void {
    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::FAILED,
        'progress' => 45,
        'current_step' => 'Document classification failed',
        'error_message' => 'All eligible LLM providers failed.',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiTaskPolling', false);
});

it('handles a missing ai task and stops polling', function (): void {
    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', 999999)
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiTaskPolling', false);
});

it('handles a completed ai task with a missing result', function (): void {
    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::COMPLETED,
        'progress' => 100,
        'current_step' => 'Completed',
        'result' => null,
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiTaskPolling', false);
});

it('handles a completed ai task with an incomplete result', function (): void {
    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::COMPLETED,
        'progress' => 100,
        'current_step' => 'Completed',
        'result' => [
            'description' => 'Generated pharmaceutical QMS description.',
            'description_reasoning' => 'Suitable for the selected department.',
            'category_id' => null,
            'document_type_id' => null,
            'regulation_tag_ids' => [],
        ],
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiTaskPolling', false);
});

it('handles a completed ai task with invalid regulation tag ids', function (): void {
    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::COMPLETED,
        'progress' => 100,
        'current_step' => 'Completed',
        'result' => [
            'description' => 'Generated pharmaceutical QMS description.',
            'description_reasoning' => 'Suitable for the selected department.',
            'category_id' => 10,
            'document_type_id' => 20,
            'regulation_tag_ids' => 'invalid',
        ],
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiTaskPolling', false);
});

it('handles a cancelled ai task and stops polling', function (): void {
    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::CANCELLED,
        'progress' => 30,
        'current_step' => 'Cancelled',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiTaskPolling', false);
});

it('does nothing when metadata ai polling is disabled', function (): void {
    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::PROCESSING,
        'progress' => 70,
        'current_step' => 'Processing',
    ]);

    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', false)
        ->set('metadataAiProgress', 10)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiProgress', 10)
        ->assertSet('metadataAiTaskPolling', false);
});

it('does nothing when no metadata ai task id is available', function (): void {
    Livewire::test(CreateDocumentTemplate::class)
        ->set('metadataAiTaskId', null)
        ->set('metadataAiTaskPolling', true)
        ->set('metadataAiProgress', 10)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiProgress', 10);
});
