<?php

declare(strict_types=1);

use App\Filament\Resources\SopTemplates\Pages\CreateSopTemplate;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use App\Jobs\ProcessSopTemplateMetadataAiJob;
use App\Models\AiTask;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use App\Services\AI\Enums\AiTaskStatus;
use Illuminate\Support\Facades\Bus;

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

it('can mount the create sop template page', function (): void {
    expect(auth()->check())
        ->toBeTrue()
        ->and(auth()->id())
        ->toBe($this->user->getKey());

    Livewire::test(CreateSopTemplate::class)
        ->assertSuccessful();
});

it('creates an ai task and dispatches the metadata processing job', function (): void {
    $department = Department::factory()->create([
        'name' => 'Production',
    ]);

    $component = Livewire::test(CreateSopTemplate::class)
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
        ProcessSopTemplateMetadataAiJob::class,
        fn (ProcessSopTemplateMetadataAiJob $job): bool =>
            $job->aiTaskId === $task->getKey(),
    );
});

it('stores the correct template metadata in the ai task input', function (): void {
    $department = Department::factory()->create([
        'name' => 'Quality Assurance',
    ]);

    Livewire::test(CreateSopTemplate::class)
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
        ProcessSopTemplateMetadataAiJob::class,
    );
});

it('prevents duplicate ai task dispatch while metadata generation is running', function (): void {
    $department = Department::factory()->create([
        'name' => 'Production',
    ]);

    $component = Livewire::test(CreateSopTemplate::class)
        ->fillForm([
            'name' => 'Equipment Cleaning Procedure',
            'department_id' => $department->getKey(),
        ])
        ->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(1);

    Bus::assertDispatchedTimes(
        ProcessSopTemplateMetadataAiJob::class,
        1,
    );

    $component->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(1);

    Bus::assertDispatchedTimes(
        ProcessSopTemplateMetadataAiJob::class,
        1,
    );
});

it('does not create an ai task when the template name is missing', function (): void {
    $department = Department::factory()->create([
        'name' => 'Production',
    ]);

    Livewire::test(CreateSopTemplate::class)
        ->fillForm([
            'name' => '',
            'department_id' => $department->getKey(),
        ])
        ->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(0);

    Bus::assertNotDispatched(
        ProcessSopTemplateMetadataAiJob::class,
    );
});

it('does not create an ai task when the department is missing', function (): void {
    Livewire::test(CreateSopTemplate::class)
        ->fillForm([
            'name' => 'Equipment Cleaning Procedure',
            'department_id' => null,
        ])
        ->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(0);

    Bus::assertNotDispatched(
        ProcessSopTemplateMetadataAiJob::class,
    );
});

it('does not create an ai task when the selected department does not exist', function (): void {
    Livewire::test(CreateSopTemplate::class)
        ->fillForm([
            'name' => 'Equipment Cleaning Procedure',
            'department_id' => 999999,
        ])
        ->call('startMetadataAiGeneration');

    expect(AiTask::query()->count())
        ->toBe(0);

    Bus::assertNotDispatched(
        ProcessSopTemplateMetadataAiJob::class,
    );
});

it('refreshes ai task progress while processing', function (): void {
    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::PROCESSING,
        'progress' => 65,
        'current_step' => 'Classifying document metadata',
    ]);

    Livewire::test(CreateSopTemplate::class)
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
        'category_id' => $category->getKey(),
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

    Livewire::test(CreateSopTemplate::class)
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

    Livewire::test(CreateSopTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiTaskPolling', false);
});

it('handles a missing ai task and stops polling', function (): void {
    Livewire::test(CreateSopTemplate::class)
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

    Livewire::test(CreateSopTemplate::class)
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

    Livewire::test(CreateSopTemplate::class)
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

    Livewire::test(CreateSopTemplate::class)
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

    Livewire::test(CreateSopTemplate::class)
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

    Livewire::test(CreateSopTemplate::class)
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', false)
        ->set('metadataAiProgress', 10)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiProgress', 10)
        ->assertSet('metadataAiTaskPolling', false);
});

it('does nothing when no metadata ai task id is available', function (): void {
    Livewire::test(CreateSopTemplate::class)
        ->set('metadataAiTaskId', null)
        ->set('metadataAiTaskPolling', true)
        ->set('metadataAiProgress', 10)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiProgress', 10);
});
