<?php

declare(strict_types=1);

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\ControlledDocuments\RelationManagers\ApprovalRelationManager;
use App\Filament\Resources\DocumentTemplateApprovalInstances\Pages\ViewDocumentTemplateApprovalInstance;
use App\Filament\Resources\LogDocuments\Pages\ViewLogDocument;
use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateApprovalEvent;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\ReportTemplate;
use App\Models\SopApproval;
use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms']);

    foreach ([
        'Approve:ControlledDocument',
        'Approve:SopApproval',
        'Decide:DocumentTemplateApproval',
        'View:ControlledDocument',
        'View:DocumentTemplate',
        'View:DocumentTemplateApproval',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->department = Department::factory()->create();
    $this->reviewerRole = Role::findOrCreate('approval experience reviewer', 'web');
    $this->author = User::factory()->create(['department_id' => $this->department]);
    $this->reviewer = User::factory()->create(['department_id' => $this->department]);
    $this->reviewer->assignRole([
        $this->reviewerRole,
        Role::findOrCreate('panel_user', 'web'),
    ]);
    $this->reviewer->givePermissionTo([
        'Approve:ControlledDocument',
        'Approve:SopApproval',
        'Decide:DocumentTemplateApproval',
        'View:ControlledDocument',
        'View:DocumentTemplate',
        'View:DocumentTemplateApproval',
    ]);

    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $category = DocumentCategory::factory()->create();
    $this->reportTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
        'created_by' => $this->author,
        'updated_by' => $this->author,
    ]);
    $this->template = DocumentTemplate::factory()->create([
        'category_id' => $category,
        'department_id' => $this->department,
        'document_type_id' => $documentType,
        'report_template_id' => $this->reportTemplate,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
        'created_by' => $this->author,
    ]);
    $this->templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $this->template,
        'created_by' => $this->author,
        'submitted_by' => $this->author,
        'submitted_at' => now(),
        'approval_status' => TemplateApprovalStatus::Submitted,
        'change_reason' => 'Review the updated cleaning responsibilities.',
    ]);
    $this->workflow = SopWorkflow::factory()->create([
        'department_id' => $this->department,
        'is_active' => true,
    ]);
    $this->step = SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'step_no' => 1,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'is_mandatory' => true,
    ]);
    $this->templateApproval = DocumentTemplateApprovalInstance::factory()->create([
        'document_template_version_id' => $this->templateVersion,
        'workflow_id' => $this->workflow,
        'workflow_step_id' => $this->step,
    ]);
    DocumentTemplateApprovalEvent::factory()->create([
        'document_template_version_id' => $this->templateVersion,
        'actor_id' => $this->author,
        'reason' => 'Focus on cleaning responsibilities and approval roles.',
    ]);
    $this->document = ControlledDocument::factory()->create([
        'template_id' => $this->template,
        'template_version_id' => $this->templateVersion,
        'department_id' => $this->department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
        'created_by' => $this->author,
        'owner_id' => $this->author,
    ]);
    $this->documentApproval = SopApproval::factory()->create([
        'document_id' => $this->document,
        'workflow_step_id' => $this->step,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);

    Gate::before(static fn (): bool => true);
    $this->actingAs($this->reviewer);
});

it('places signed controlled-document decisions at the top of the review page', function (): void {
    Livewire::test(ViewControlledDocument::class, ['record' => $this->document->getRouteKey()])
        ->assertSee('Action required: Step 1')
        ->assertActionVisible('approveCurrentStep')
        ->assertActionVisible('previewWithPrintTemplate')
        ->assertActionVisible('returnCurrentStep')
        ->assertActionVisible('rejectCurrentStep')
        ->mountAction('approveCurrentStep')
        ->assertMountedActionModalSee('electronically signed')
        ->assertMountedActionModalSee('signed audit trail')
        ->fillForm(['comments' => 'Checked document content and responsibilities.'])
        ->callMountedAction()
        ->assertNotified();

    expect($this->documentApproval->refresh()->approvalDecision?->code)->toBe(ApprovalDecision::APPROVED)
        ->and($this->documentApproval->signature_hash)->not->toBeNull()
        ->and($this->document->refresh()->documentStatus?->code)->toBe(DocumentStatus::APPROVED);
});

it('shows print preview on log documents under review', function (): void {
    $logType = DocumentType::query()->where('code', DocumentType::LOG)->firstOrFail();
    $logType->update(['is_issuable' => true, 'requires_sop_reference' => false]);
    $logTemplate = DocumentTemplate::factory()->create([
        'category_id' => $this->template->category_id,
        'department_id' => $this->department,
        'document_type_id' => $logType,
        'report_template_id' => $this->reportTemplate,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'created_by' => $this->author,
    ]);
    $logVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $logTemplate,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'created_by' => $this->author,
    ]);
    $logDocument = ControlledDocument::factory()->create([
        'template_id' => $logTemplate,
        'template_version_id' => $logVersion,
        'department_id' => $this->department,
        'category_id' => $this->template->category_id,
        'document_type_id' => $logType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
        'created_by' => $this->author,
        'owner_id' => $this->author,
    ]);

    Livewire::test(ViewLogDocument::class, ['record' => $logDocument->getRouteKey()])
        ->assertActionVisible('previewWithPrintTemplate');

    Livewire::test(ViewControlledDocument::class, ['record' => $logDocument->getRouteKey()])
        ->assertActionVisible('previewWithPrintTemplate');
});

it('shows approve for checker workflow steps in controlled-document approval history', function (): void {
    Livewire::test(ApprovalRelationManager::class, [
        'ownerRecord' => $this->document,
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertActionVisible(TestAction::make('approve')->table($this->documentApproval))
        ->assertActionVisible(TestAction::make('previewWithPrintTemplate')->table());
});

it('provides a complete template decision workspace from the approval queue', function (): void {
    Livewire::test(ViewDocumentTemplateApprovalInstance::class, [
        'record' => $this->templateApproval->getRouteKey(),
    ])
        ->assertSee('Template under review')
        ->assertSee('Your workflow task')
        ->assertSee('Review the updated cleaning responsibilities.')
        ->assertSee('Focus on cleaning responsibilities and approval roles.')
        ->assertActionVisible('previewTemplate')
        ->assertActionVisible('approve')
        ->assertActionVisible('return')
        ->assertActionVisible('reject')
        ->mountAction('approve')
        ->assertMountedActionModalSee('electronically signed')
        ->assertMountedActionModalSee('signed approval record')
        ->fillForm(['comments' => 'Template structure and responsibilities are acceptable.'])
        ->callMountedAction()
        ->assertNotified();

    expect($this->templateApproval->refresh()->decision_code)->toBe(ApprovalDecisionCode::APPROVED->value)
        ->and($this->templateApproval->signature_hash)->not->toBeNull()
        ->and($this->templateVersion->refresh()->approval_status)->toBe(TemplateApprovalStatus::Approved);
});
