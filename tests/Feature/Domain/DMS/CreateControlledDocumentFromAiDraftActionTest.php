<?php

declare(strict_types=1);

use App\Filament\Pages\ControlledDocumentDraftAssistant;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentDraftSession;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Models\VariableDataType;
use App\Services\AI\Actions\CreateControlledDocumentFromAiDraftAction;
use App\Services\AI\Enums\ControlledDocumentDraftSessionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    config()->set('modules.enabled', ['dms', 'ai']);
    Gate::before(static fn (): bool => true);

    TemplateStatus::query()->create(['code' => TemplateStatus::DRAFT, 'name' => 'Draft']);
    TemplateStatus::query()->create(['code' => TemplateStatus::PUBLISHED, 'name' => 'Published']);
    DocumentStatus::query()->create(['code' => DocumentStatus::DRAFT, 'name' => 'Draft']);
    VariableDataType::query()->create(['code' => VariableDataType::TEXT, 'name' => 'Text']);
    Organization::factory()->create(['is_default' => true, 'is_active' => true]);

    $this->creator = User::factory()->create();
    $this->owner = User::factory()->create();
    $this->department = Department::factory()->create([
        'name' => 'Quality Assurance',
        'code' => 'QA',
    ]);
    $template = DocumentTemplate::factory()->create([
        'department_id' => $this->department,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => DocumentType::factory()->state(['code' => DocumentType::POLICY]),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);
    $version = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template,
        'version' => 1,
    ]);
    DocumentTemplateVariable::factory()->create([
        'template_version_id' => $version,
        'name' => 'purpose',
        'label' => 'Purpose',
        'required' => true,
        'default_value' => null,
    ]);
    DocumentTemplateSection::factory()->create([
        'template_version_id' => $version,
        'title' => 'Purpose',
        'section_order' => 1,
        'content' => '{{purpose}}',
    ]);

    $this->session = ControlledDocumentDraftSession::factory()->create([
        'created_by' => $this->creator,
        'owner_id' => $this->owner,
        'template_id' => $template,
        'template_version_id' => $version,
        'status' => ControlledDocumentDraftSessionStatus::PREVIEW_READY,
        'title' => 'Change Control Policy',
        'brief' => ['purpose' => 'Define change controls.'],
        'draft_variables' => ['purpose' => 'Define change controls.'],
        'preview_revision' => 1,
    ]);
    $this->session->forceFill([
        'preview_hash' => $this->session->calculatePreviewHash(),
    ])->save();
});

it('creates exactly one draft through the canonical template action', function (): void {
    $action = app(CreateControlledDocumentFromAiDraftAction::class);
    $document = $action->execute(
        $this->session,
        $this->creator,
        $this->session->preview_hash,
    );
    $repeated = $action->execute(
        $this->session->refresh(),
        $this->creator,
        $this->session->preview_hash,
    );

    expect($document->is($repeated))->toBeTrue()
        ->and(ControlledDocument::query()->count())->toBe(1)
        ->and($document->documentStatus->code)->toBe(DocumentStatus::DRAFT)
        ->and($document->sections->first()->content)->toBe('Define change controls.')
        ->and($this->session->refresh()->status)->toBe(ControlledDocumentDraftSessionStatus::CONFIRMED)
        ->and($this->session->controlled_document_id)->toBe($document->id);
});

it('rejects confirmation when the preview hash is stale', function (): void {
    expect(fn () => app(CreateControlledDocumentFromAiDraftAction::class)->execute(
        $this->session,
        $this->creator,
        str_repeat('0', 64),
    ))->toThrow(ValidationException::class, 'preview changed')
        ->and(ControlledDocument::query()->count())->toBe(0);
});

it('creates an existing AI preview that contains human-readable relationship values', function (): void {
    $departmentType = VariableDataType::query()->create([
        'code' => VariableDataType::DEPARTMENT,
        'name' => 'Department',
    ]);
    $employeeType = VariableDataType::query()->create([
        'code' => VariableDataType::EMPLOYEE,
        'name' => 'Employee',
    ]);
    $selectType = VariableDataType::query()->create([
        'code' => VariableDataType::SELECT,
        'name' => 'Select',
    ]);
    $dateType = VariableDataType::query()->create([
        'code' => VariableDataType::DATE,
        'name' => 'Date',
    ]);
    $approver = User::factory()->create(['name' => 'SOP Approver']);

    foreach ([
        ['name' => 'department', 'label' => 'Department', 'type' => $departmentType, 'options' => null, 'required' => true],
        ['name' => 'prepared_by', 'label' => 'Prepared By', 'type' => $employeeType, 'options' => null, 'required' => false],
        ['name' => 'approved_by', 'label' => 'Approved By', 'type' => $employeeType, 'options' => null, 'required' => false],
        ['name' => 'effective_date', 'label' => 'Effective Date', 'type' => $dateType, 'options' => null, 'required' => false],
        ['name' => 'review_date', 'label' => 'Review Date', 'type' => $dateType, 'options' => null, 'required' => false],
        [
            'name' => 'equipment_category',
            'label' => 'Equipment Category',
            'type' => $selectType,
            'options' => ['production' => 'Production Equipment'],
            'required' => false,
        ],
    ] as $definition) {
        DocumentTemplateVariable::factory()->create([
            'template_version_id' => $this->session->template_version_id,
            'name' => $definition['name'],
            'label' => $definition['label'],
            'variable_data_type_id' => $definition['type'],
            'options' => $definition['options'],
            'required' => $definition['required'],
            'default_value' => null,
        ]);
    }

    DocumentTemplateSection::query()
        ->where('template_version_id', $this->session->template_version_id)
        ->update(['content' => '{{department}}|{{approved_by}}|{{prepared_by}}|{{equipment_category}}']);

    $this->session->forceFill([
        'draft_variables' => [
            'purpose' => 'Define change controls.',
            'department' => 'QA',
            'prepared_by' => 'QualiGxP',
            'approved_by' => 'SOP Approver',
            'equipment_category' => 'Hardware',
            'effective_date' => '2026-09-01',
            'review_date' => '2029-08-29',
        ],
        'preview_revision' => 2,
    ]);
    $this->session->preview_hash = $this->session->calculatePreviewHash();
    $this->session->save();

    $document = app(CreateControlledDocumentFromAiDraftAction::class)->execute(
        $this->session,
        $this->creator,
        $this->session->preview_hash,
    );
    $variables = $document->variables->pluck('value', 'variable_name');

    expect($document->sections->first()->content)
        ->toBe('Quality Assurance|SOP Approver||')
        ->and($variables['department'])->toBe((string) $this->department->getKey())
        ->and($variables['approved_by'])->toBe((string) $approver->getKey())
        ->and($variables['prepared_by'])->toBe('')
        ->and($variables['equipment_category'])->toBe('')
        ->and($document->effective_date->toDateString())->toBe('2026-09-01')
        ->and($document->review_date->toDateString())->toBe('2029-08-29');
});

it('shows final validation failures beside the confirm button', function (): void {
    $integerType = VariableDataType::query()->create([
        'code' => VariableDataType::INTEGER,
        'name' => 'Integer',
    ]);
    DocumentTemplateVariable::factory()->create([
        'template_version_id' => $this->session->template_version_id,
        'name' => 'training_days',
        'label' => 'Training Days',
        'variable_data_type_id' => $integerType,
        'required' => false,
        'default_value' => null,
    ]);
    $this->session->forceFill([
        'draft_variables' => [
            'purpose' => 'Define change controls.',
            'training_days' => 'not-a-number',
        ],
        'preview_revision' => 2,
    ]);
    $this->session->preview_hash = $this->session->calculatePreviewHash();
    $this->session->save();

    Livewire::actingAs($this->creator)
        ->test(ControlledDocumentDraftAssistant::class)
        ->set('draftSessionId', $this->session->getKey())
        ->set('expectedPreviewHash', $this->session->preview_hash)
        ->call('createDraft')
        ->assertHasErrors(['confirmation']);

    expect(ControlledDocument::query()->count())->toBe(0);
});
