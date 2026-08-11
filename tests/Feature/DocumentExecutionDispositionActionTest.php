<?php

declare(strict_types=1);

use App\Filament\Resources\DocumentExecutions\Pages\ViewDocumentExecution;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentExecution;
use App\Models\DocumentExecutionItem;
use App\Models\DocumentExecutionSection;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms']);
    $this->seed(LookupTableSeeder::class);

    foreach ([
        'ViewAny:DocumentExecution',
        'View:DocumentExecution',
        'Submit:DocumentExecution',
        'Review:DocumentExecution',
        'Approve:DocumentExecution',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->qaApprover = User::factory()->create();
    $this->qaApprover->givePermissionTo([
        'ViewAny:DocumentExecution',
        'View:DocumentExecution',
        'Submit:DocumentExecution',
        'Review:DocumentExecution',
        'Approve:DocumentExecution',
    ]);
    $this->actingAs($this->qaApprover);

    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', DocumentType::BATCH_RECORD)->firstOrFail();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create(['document_template_id' => $template]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);
    $issuance = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issuance_type' => DocumentIssuance::TYPE_EXECUTION,
    ]);
    $this->execution = DocumentExecution::factory()->create([
        'document_issuance_id' => $issuance,
        'document_type_code' => DocumentType::BATCH_RECORD,
        'workflow_configuration' => [
            'requires_qa_approval' => true,
            'requires_disposition' => true,
        ],
        'status' => DocumentExecution::STATUS_QA_REVIEW,
        'completed_by' => User::factory(),
        'reviewed_by' => User::factory(),
        'disposition' => DocumentExecution::DISPOSITION_PENDING,
    ]);
});

it('records a QA disposition and displays the success notification', function (): void {
    Livewire::test(ViewDocumentExecution::class, ['record' => $this->execution->id])
        ->callAction('qaDisposition', [
            'disposition' => DocumentExecution::DISPOSITION_RELEASED,
            'qa_notes' => 'Batch documentation and reconciliation reviewed.',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('QA disposition recorded and execution closed.');

    expect($this->execution->fresh())
        ->status->toBe(DocumentExecution::STATUS_CLOSED)
        ->disposition->toBe(DocumentExecution::DISPOSITION_RELEASED)
        ->qa_approved_by->toBe($this->qaApprover->id)
        ->qa_notes->toBe('Batch documentation and reconciliation reviewed.')
        ->closed_at->not->toBeNull();
});

it('notifies the QA approver when disposition is blocked by lack of independence', function (): void {
    $this->execution->update([
        'completed_by' => $this->qaApprover->id,
    ]);

    Livewire::test(ViewDocumentExecution::class, ['record' => $this->execution->id])
        ->callAction('qaDisposition', [
            'disposition' => DocumentExecution::DISPOSITION_RELEASED,
            'qa_notes' => 'Ready for release.',
        ])
        ->assertNotified(
            Notification::make()
                ->danger()
                ->title('QA disposition blocked')
                ->body('The QA approver must be independent of execution and production review.')
                ->persistent()
        );

    expect($this->execution->fresh())
        ->status->toBe(DocumentExecution::STATUS_QA_REVIEW)
        ->disposition->toBe(DocumentExecution::DISPOSITION_PENDING)
        ->qa_approved_by->toBeNull()
        ->closed_at->toBeNull();
});

it('notifies when supervisor review is blocked because the completer cannot review their own record', function (): void {
    $this->execution->update([
        'workflow_configuration' => [
            'requires_supervisor_review' => true,
            'requires_qa_approval' => true,
            'requires_disposition' => true,
        ],
        'status' => DocumentExecution::STATUS_UNDER_REVIEW,
        'completed_by' => $this->qaApprover->id,
        'reviewed_by' => null,
        'disposition' => DocumentExecution::DISPOSITION_PENDING,
    ]);

    Livewire::test(ViewDocumentExecution::class, ['record' => $this->execution->id])
        ->callAction('supervisorReview', [
            'review_notes' => 'Self-review attempt.',
        ])
        ->assertNotified(
            Notification::make()
                ->danger()
                ->title('Supervisor review blocked')
                ->body('The reviewer must be different from the person who completed the record.')
                ->persistent()
        );

    expect($this->execution->fresh())
        ->status->toBe(DocumentExecution::STATUS_UNDER_REVIEW)
        ->reviewed_by->toBeNull();
});

it('completes supervisor review and advances the execution to QA review', function (): void {
    $completer = User::factory()->create();
    $this->execution->update([
        'workflow_configuration' => [
            'requires_supervisor_review' => true,
            'requires_qa_approval' => true,
            'requires_disposition' => true,
        ],
        'status' => DocumentExecution::STATUS_UNDER_REVIEW,
        'completed_by' => $completer->id,
        'reviewed_by' => null,
        'disposition' => DocumentExecution::DISPOSITION_PENDING,
    ]);

    Livewire::test(ViewDocumentExecution::class, ['record' => $this->execution->id])
        ->callAction('supervisorReview', [
            'review_notes' => 'Entries checked.',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('Supervisor review completed.');

    expect($this->execution->fresh())
        ->status->toBe(DocumentExecution::STATUS_QA_REVIEW)
        ->reviewed_by->toBe($this->qaApprover->id)
        ->review_notes->toBe('Entries checked.');
});

it('notifies the executor when completion is blocked by missing verification', function (): void {
    $this->execution->update([
        'workflow_configuration' => [
            'requires_item_verification' => true,
            'requires_supervisor_review' => true,
        ],
        'status' => DocumentExecution::STATUS_IN_PROGRESS,
        'completed_by' => null,
        'reviewed_by' => null,
        'disposition' => DocumentExecution::DISPOSITION_NOT_APPLICABLE,
    ]);
    $section = DocumentExecutionSection::factory()->for($this->execution, 'execution')->create([
        'title' => 'Batch and Product Details',
        'status' => 'completed',
    ]);
    DocumentExecutionItem::factory()->for($section, 'section')->create([
        'label' => 'Product Name',
        'response' => 'Test Product',
        'verified_by' => null,
    ]);

    Livewire::test(ViewDocumentExecution::class, ['record' => $this->execution->id])
        ->callAction('completeExecution')
        ->assertNotified(
            Notification::make()
                ->danger()
                ->title('Execution submission blocked')
                ->body('Independent verification is required for: Batch and Product Details: Product Name.')
                ->persistent()
        );

    expect($this->execution->fresh())
        ->status->toBe(DocumentExecution::STATUS_IN_PROGRESS)
        ->completed_by->toBeNull();
});
