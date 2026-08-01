<?php

declare(strict_types=1);

use App\Filament\Resources\DocumentTemplates\Pages\EditDocumentTemplate;
use App\Jobs\ProcessDocumentTemplateMetadataAiJob;
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
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $role = Role::findOrCreate(
        'super_admin',
        config('auth.defaults.guard'),
    );

    $this->user->assignRole($role);

    $this->actingAs($this->user);

    Gate::before(
        fn (User $user, string $ability): ?bool => true,
    );

    TemplateStatus::query()->create([
        'name' => 'Draft',
        'code' => TemplateStatus::DRAFT,
    ]);
});

it('can mount the edit sop template page', function (): void {
    $template = DocumentTemplate::factory()->create();

    Livewire::test(
        EditDocumentTemplate::class,
        [
            'record' => $template->getKey(),
        ],
    )
        ->assertSuccessful()
        ->assertFormSet([
            'name' => $template->name,
        ]);
});

it('loads the template regulation tags in the edit form', function (): void {
    $template = DocumentTemplate::factory()->create();
    $tag = RegulationTag::query()->create([
        'name' => 'Good Manufacturing Practice',
        'code' => 'GMP-EDIT',
    ]);

    $template->regulationTags()->attach($tag);

    Livewire::test(EditDocumentTemplate::class, [
        'record' => $template->getKey(),
    ])->assertFormSet([
        'regulationTags' => [$tag->getKey()],
    ]);
});

it('creates an ai task using the currently edited form metadata', function (): void {
    Bus::fake();

    $originalDepartment = Department::factory()->create([
        'name' => 'Production',
    ]);

    $editedDepartment = Department::factory()->create([
        'name' => 'Quality Assurance',
    ]);

    $template = DocumentTemplate::factory()->create([
        'name' => 'Original Template Name',
        'department_id' => $originalDepartment->getKey(),
    ]);

    Livewire::test(
        EditDocumentTemplate::class,
        [
            'record' => $template->getKey(),
        ],
    )
        ->fillForm([
            'name' => 'Deviation Management Procedure',
            'department_id' => $editedDepartment->getKey(),
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

    $template->refresh();

    expect($template->name)
        ->toBe('Original Template Name')
        ->and($template->department_id)
        ->toBe($originalDepartment->getKey());
});

it('applies a completed ai task result to the edit form without persisting changes', function (): void {
    $originalDepartment = Department::factory()->create([
        'name' => 'Production',
    ]);

    $category = DocumentCategory::factory()->create([
        'name' => 'Procedures',
        'code' => 'PROCEDURE',
    ]);

    $documentType = DocumentType::factory()->create([
        'name' => 'AI Metadata Test Procedure',
        'code' => 'AI_META_TEST_SOP',
    ]);

    $regulationTag = RegulationTag::query()->create([
        'name' => 'Good Manufacturing Practice',
        'code' => 'GMP',
        'description' => 'Good Manufacturing Practice requirements.',
    ]);

    $documentType
        ->regulationTags()
        ->attach($regulationTag);

    $template = DocumentTemplate::factory()->create([
        'name' => 'Equipment Cleaning Procedure',
        'description' => 'Original description.',
        'department_id' => $originalDepartment->getKey(),
    ]);

    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::COMPLETED,
        'progress' => 100,
        'current_step' => 'Completed',
        'result' => [
            'description' => 'Defines the requirements and responsibilities for cleaning manufacturing equipment.',
            'description_reasoning' => 'The generated description reflects the operational requirements of the Production department.',
            'category_id' => $category->getKey(),
            'document_type_id' => $documentType->getKey(),
            'regulation_tag_ids' => [
                $regulationTag->getKey(),
            ],
        ],
    ]);

    Livewire::test(
        EditDocumentTemplate::class,
        [
            'record' => $template->getKey(),
        ],
    )
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
        ->assertSet('metadataAiTaskPolling', false);

    $template->refresh();

    expect($template->description)
        ->toBe('Original description.');
});

it('persists ai generated metadata when the edited template is saved', function (): void {
    $department = Department::factory()->create([
        'name' => 'Production',
    ]);

    $category = DocumentCategory::factory()->create([
        'name' => 'AI Save Test Procedures',
        'code' => 'AI_SAVE_TEST_PROCEDURE',
    ]);

    $documentType = DocumentType::factory()->create([
        'name' => 'AI Save Test Procedure',
        'code' => 'AI_SAVE_TEST_SOP',
    ]);

    $regulationTag = RegulationTag::query()->create([
        'name' => 'AI Save Test GMP',
        'code' => 'AI_SAVE_TEST_GMP',
        'description' => 'Test regulatory requirements.',
    ]);

    $documentType
        ->regulationTags()
        ->attach($regulationTag);

    $template = DocumentTemplate::factory()->create([
        'name' => 'Equipment Cleaning Procedure',
        'description' => 'Original description.',
        'department_id' => $department->getKey(),
    ]);

    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::COMPLETED,
        'progress' => 100,
        'current_step' => 'Completed',
        'result' => [
            'description' => 'Defines the requirements and responsibilities for cleaning manufacturing equipment.',
            'description_reasoning' => 'Suitable for the Production department.',
            'category_id' => $category->getKey(),
            'document_type_id' => $documentType->getKey(),
            'regulation_tag_ids' => [
                $regulationTag->getKey(),
            ],
        ],
    ]);

    Livewire::test(
        EditDocumentTemplate::class,
        [
            'record' => $template->getKey(),
        ],
    )
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
        ->call('save')
        ->assertHasNoFormErrors();

    $template->refresh();

    expect($template->description)
        ->toBe('Defines the requirements and responsibilities for cleaning manufacturing equipment.')
        ->and($template->category_id)
        ->toBe($category->getKey())
        ->and($template->document_type_id)
        ->toBe($documentType->getKey())
        ->and($template->regulationTags->modelKeys())
        ->toBe([
            $regulationTag->getKey(),
        ]);
});

it('prevents duplicate ai task dispatch while metadata generation is running', function (): void {
    Bus::fake();

    $department = Department::factory()->create([
        'name' => 'Quality Assurance',
    ]);

    $template = DocumentTemplate::factory()->create([
        'name' => 'Deviation Management Procedure',
        'department_id' => $department->getKey(),
    ]);

    $component = Livewire::test(
        EditDocumentTemplate::class,
        [
            'record' => $template->getKey(),
        ],
    )
        ->fillForm([
            'name' => 'Updated Deviation Management Procedure',
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

it('handles a failed ai task and stops polling without changing the edit form', function (): void {
    $department = Department::factory()->create([
        'name' => 'Quality Assurance',
    ]);

    $template = DocumentTemplate::factory()->create([
        'name' => 'Deviation Management Procedure',
        'description' => 'Original template description.',
        'department_id' => $department->getKey(),
    ]);

    $task = AiTask::factory()->create([
        'status' => AiTaskStatus::FAILED,
        'progress' => 50,
        'current_step' => 'Document classification failed.',
        'error_message' => 'All eligible LLM providers failed.',
    ]);

    Livewire::test(
        EditDocumentTemplate::class,
        [
            'record' => $template->getKey(),
        ],
    )
        ->set('metadataAiTaskId', $task->getKey())
        ->set('metadataAiTaskPolling', true)
        ->call('refreshMetadataAiTask')
        ->assertSet('metadataAiTaskPolling', false)
        ->assertFormSet([
            'name' => 'Deviation Management Procedure',
            'description' => 'Original template description.',
            'department_id' => $department->getKey(),
        ]);

    $template->refresh();

    expect($template->name)
        ->toBe('Deviation Management Procedure')
        ->and($template->description)
        ->toBe('Original template description.')
        ->and($template->department_id)
        ->toBe($department->getKey());
});
