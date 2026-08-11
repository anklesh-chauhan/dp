<?php

declare(strict_types=1);

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Domain\Shared\Services\CurrentPendingApprovalStepResolver;
use App\Filament\Resources\ControlledDocuments\Pages\ListControlledDocuments;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\DocumentTemplates\Pages\ListDocumentTemplates;
use App\Filament\Resources\DocumentTemplates\Pages\ViewDocumentTemplate;
use App\Filament\Resources\LogDocuments\Pages\ViewLogDocument;
use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\SopApproval;
use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
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
        'View:ControlledDocument',
        'View:DocumentTemplate',
        'ViewAny:ControlledDocument',
        'ViewAny:DocumentTemplate',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->department = Department::factory()->create(['name' => 'Quality Assurance']);
    $this->reviewerRole = Role::findOrCreate('pending step reviewer', 'web');
    $this->approverRole = Role::findOrCreate('pending step approver', 'web');
    $this->author = User::factory()->create(['department_id' => $this->department]);
    $this->viewer = User::factory()->create(['department_id' => $this->department]);
    $this->viewer->assignRole(Role::findOrCreate('panel_user', 'web'));
    $this->viewer->givePermissionTo([
        'View:ControlledDocument',
        'View:DocumentTemplate',
        'ViewAny:ControlledDocument',
        'ViewAny:DocumentTemplate',
    ]);

    $this->documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $this->category = DocumentCategory::factory()->create();
    $this->workflow = SopWorkflow::factory()->create([
        'department_id' => $this->department,
        'is_active' => true,
    ]);
    $this->stepOne = SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'step_no' => 1,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'is_mandatory' => true,
    ]);
    $this->stepTwo = SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'step_no' => 2,
        'role_id' => $this->approverRole,
        'department_id' => $this->department,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::APPROVER),
        'is_mandatory' => true,
    ]);

    Gate::before(static fn (): bool => true);
    $this->actingAs($this->viewer);
});

/**
 * @return array{0: DocumentTemplate, 1: DocumentTemplateVersion}
 */
function makeUnderReviewTemplateContext(?DocumentType $documentType = null): array
{
    $template = DocumentTemplate::factory()->create([
        'category_id' => test()->category,
        'department_id' => test()->department,
        'document_type_id' => $documentType ?? test()->documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
        'created_by' => test()->author,
    ]);
    $version = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
        'created_by' => test()->author,
        'submitted_by' => test()->author,
        'submitted_at' => now(),
        'approval_status' => TemplateApprovalStatus::Submitted,
    ]);

    return [$template, $version];
}

function makeUnderReviewDocument(
    DocumentTemplate $template,
    DocumentTemplateVersion $version,
): ControlledDocument {
    return ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $version,
        'department_id' => test()->department,
        'category_id' => test()->category,
        'document_type_id' => $template->document_type_id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
        'created_by' => test()->author,
        'owner_id' => test()->author,
    ]);
}

it('resolves the first actionable pending step for a controlled document', function (): void {
    [$template, $version] = makeUnderReviewTemplateContext();
    $document = makeUnderReviewDocument($template, $version);

    SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $this->stepOne,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);
    SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $this->stepTwo,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);

    $pending = app(CurrentPendingApprovalStepResolver::class)->forControlledDocument($document);

    expect($pending)->not->toBeNull()
        ->and($pending->stepNo)->toBe(1)
        ->and($pending->roleName)->toBe('pending step reviewer')
        ->and($pending->stepTypeName)->toBe('Checker')
        ->and($pending->departmentName)->toBe('Quality Assurance')
        ->and($document->displayStatusLabel())->toContain('Under Review')
        ->and($document->displayStatusLabel())->toContain('Step 1 · Checker · pending step reviewer');
});

it('advances the pending step after the previous mandatory approval', function (): void {
    [$template, $version] = makeUnderReviewTemplateContext();
    $document = makeUnderReviewDocument($template, $version);

    SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $this->stepOne,
        'approved_by' => $this->viewer,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
        'approved_at' => now(),
    ]);
    SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $this->stepTwo,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);

    $pending = app(CurrentPendingApprovalStepResolver::class)->forControlledDocument($document->fresh());

    expect($pending)->not->toBeNull()
        ->and($pending->stepNo)->toBe(2)
        ->and($pending->label())->toContain('Step 2')
        ->and($pending->label())->toContain('pending step approver');
});

it('resolves the current pending template approval step', function (): void {
    [$template, $version] = makeUnderReviewTemplateContext();
    $submissionUuid = (string) str()->uuid();
    DocumentTemplateApprovalInstance::factory()->create([
        'document_template_version_id' => $version,
        'workflow_id' => $this->workflow,
        'workflow_step_id' => $this->stepOne,
        'submission_uuid' => $submissionUuid,
        'decision_code' => ApprovalDecisionCode::PENDING->value,
    ]);
    DocumentTemplateApprovalInstance::factory()->create([
        'document_template_version_id' => $version,
        'workflow_id' => $this->workflow,
        'workflow_step_id' => $this->stepTwo,
        'submission_uuid' => $submissionUuid,
        'decision_code' => ApprovalDecisionCode::PENDING->value,
    ]);

    $pending = app(CurrentPendingApprovalStepResolver::class)->forDocumentTemplate($template->fresh());

    expect($pending)->not->toBeNull()
        ->and($pending->stepNo)->toBe(1)
        ->and($template->fresh()->displayStatusLabel())->toContain('Under Review')
        ->and($template->fresh()->displayStatusLabel())->toContain('Step 1 · Checker · pending step reviewer');
});

it('shows pending step detail on controlled document and template views', function (): void {
    [$template, $version] = makeUnderReviewTemplateContext();
    DocumentTemplateApprovalInstance::factory()->create([
        'document_template_version_id' => $version,
        'workflow_id' => $this->workflow,
        'workflow_step_id' => $this->stepOne,
    ]);

    $document = makeUnderReviewDocument($template, $version);
    SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $this->stepOne,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);

    Livewire::test(ViewControlledDocument::class, ['record' => $document->getRouteKey()])
        ->assertSee('Waiting at Step 1 · Checker · pending step reviewer');

    Livewire::test(ViewDocumentTemplate::class, ['record' => $template->getRouteKey()])
        ->assertSee('Waiting at Step 1 · Checker · pending step reviewer');

    $logType = DocumentType::query()->where('code', DocumentType::LOG)->firstOrFail();
    [$logTemplate, $logVersion] = makeUnderReviewTemplateContext($logType);
    $logDocument = makeUnderReviewDocument($logTemplate, $logVersion);
    SopApproval::factory()->create([
        'document_id' => $logDocument,
        'workflow_step_id' => $this->stepOne,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);

    Livewire::test(ViewLogDocument::class, ['record' => $logDocument->getRouteKey()])
        ->assertSee('Waiting at Step 1 · Checker · pending step reviewer');
});

it('shows pending step detail in controlled document and template list status badges', function (): void {
    [$template, $version] = makeUnderReviewTemplateContext();
    DocumentTemplateApprovalInstance::factory()->create([
        'document_template_version_id' => $version,
        'workflow_id' => $this->workflow,
        'workflow_step_id' => $this->stepOne,
    ]);

    $document = makeUnderReviewDocument($template, $version);
    SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $this->stepOne,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);

    Livewire::test(ListControlledDocuments::class)
        ->assertCanSeeTableRecords([$document])
        ->assertSee('Step 1 · Checker · pending step reviewer');

    Livewire::test(ListDocumentTemplates::class)
        ->assertCanSeeTableRecords([$template])
        ->assertSee('Step 1 · Checker · pending step reviewer');
});
