<?php

declare(strict_types=1);

use App\Actions\Sop\ApproveDocumentAction;
use App\Actions\Sop\CreateDocumentFromTemplateAction;
use App\Actions\Sop\LockDocumentAction;
use App\Actions\Sop\ReturnDocumentAction;
use App\Actions\Sop\SubmitDocumentAction;
use App\Data\SopDocumentData;
use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopRole;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\SopWorkflow;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createApprovalWorkflow(Department $department, Role $checkerRole, Role $approverRole, ?int $approverStepDepartmentId = null): SopWorkflow
{
    $workflow = SopWorkflow::factory()->create([
        'name' => "{$department->code} Workflow",
        'department_id' => $department->id,
        'is_active' => true,
    ]);

    $workflow->steps()->create([
        'step_no' => 1,
        'role_id' => $checkerRole->id,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'department_id' => null,
        'is_mandatory' => true,
    ]);

    $workflow->steps()->create([
        'step_no' => 2,
        'role_id' => $approverRole->id,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::APPROVER),
        'department_id' => $approverStepDepartmentId,
        'is_mandatory' => true,
    ]);

    return $workflow->load('steps');
}

function createPublishedTemplate(Department $department): SopTemplate
{
    $template = SopTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => DocumentType::factory(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);

    SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
    ]);

    return $template->load('publishedVersion');
}

function grantMakerPermissions(User $user): void
{
    Permission::findOrCreate('Create:SopDocument', 'web');
    Permission::findOrCreate('Update:SopDocument', 'web');
    Permission::findOrCreate('Submit:SopDocument', 'web');

    $user->givePermissionTo(['Create:SopDocument', 'Update:SopDocument', 'Submit:SopDocument']);
    $user->assignRole(Role::findOrCreate(SopRole::MAKER, 'web'));
}

function grantApproverPermissions(User $user): void
{
    Permission::findOrCreate('Approve:SopDocument', 'web');
    $user->givePermissionTo('Approve:SopDocument');
}

it('creates documents in draft without auto-starting approval workflow', function (): void {
    $department = Department::factory()->create();
    $maker = User::factory()->create(['department_id' => $department->id]);
    grantMakerPermissions($maker);

    $template = createPublishedTemplate($department);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Cleaning SOP',
        ownerId: $maker->id,
        createdBy: $maker->id,
        variables: [],
        documentNumber: 'SOP-QA-00001',
    ));

    expect($document->documentStatus?->is(DocumentStatus::DRAFT))->toBeTrue()
        ->and($document->approvals)->toHaveCount(0);
});

it('submits a draft document through checker and approver steps', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);
    $maker = User::factory()->create(['department_id' => $department->id]);
    $checker = User::factory()->create(['department_id' => $department->id]);
    $approver = User::factory()->create(['department_id' => $department->id]);

    grantMakerPermissions($maker);

    $checkerRole = Role::findOrCreate(SopRole::CHECKER, 'web');
    $approverRole = Role::findOrCreate(SopRole::APPROVER, 'web');

    grantApproverPermissions($checker);
    grantApproverPermissions($approver);
    $checker->assignRole($checkerRole);
    $approver->assignRole($approverRole);

    createApprovalWorkflow($department, $checkerRole, $approverRole);
    $template = createPublishedTemplate($department);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Packaging SOP',
        ownerId: $maker->id,
        createdBy: $maker->id,
        variables: [],
        documentNumber: 'SOP-QA-00002',
    ));

    app(SubmitDocumentAction::class)->execute($document, $maker);

    expect($document->refresh()->documentStatus?->is(DocumentStatus::UNDER_REVIEW))->toBeTrue()
        ->and($document->approvals)->toHaveCount(2);

    $checkerApproval = $document->approvals()->whereHas('workflowStep', fn ($q) => $q->where('step_no', 1))->first();
    $approverApproval = $document->approvals()->whereHas('workflowStep', fn ($q) => $q->where('step_no', 2))->first();

    expect($checkerApproval->canBeApprovedBy($checker))->toBeTrue()
        ->and($checkerApproval->canBeApprovedBy($maker))->toBeFalse()
        ->and($approverApproval->canBeApprovedBy($approver))->toBeFalse();

    app(ApproveDocumentAction::class)->execute($checkerApproval, $checker, 'Checked');

    expect($document->refresh()->documentStatus?->is(DocumentStatus::APPROVED))->toBeTrue()
        ->and($approverApproval->refresh()->canBeApprovedBy($approver))->toBeTrue();

    app(ApproveDocumentAction::class)->execute($approverApproval, $approver, 'Approved for use');

    expect($document->refresh()->documentStatus?->is(DocumentStatus::EFFECTIVE))->toBeTrue();
});

it('returns a document to the maker for revision', function (): void {
    $department = Department::factory()->create();
    $maker = User::factory()->create(['department_id' => $department->id]);
    $checker = User::factory()->create(['department_id' => $department->id]);

    grantMakerPermissions($maker);

    $checkerRole = Role::findOrCreate(SopRole::CHECKER, 'web');
    $approverRole = Role::findOrCreate(SopRole::APPROVER, 'web');

    grantApproverPermissions($checker);
    $checker->assignRole($checkerRole);

    createApprovalWorkflow($department, $checkerRole, $approverRole);
    $template = createPublishedTemplate($department);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Returned SOP',
        ownerId: $maker->id,
        createdBy: $maker->id,
        variables: [],
        documentNumber: 'SOP-QA-00003',
    ));

    app(SubmitDocumentAction::class)->execute($document, $maker);

    $checkerApproval = $document->approvals()->whereHas('workflowStep', fn ($q) => $q->where('step_no', 1))->first();

    app(ReturnDocumentAction::class)->execute($checkerApproval, $checker, 'Revise section 3');

    expect($document->refresh()->documentStatus?->is(DocumentStatus::DRAFT))->toBeTrue()
        ->and($document->locked_by)->toBeNull()
        ->and($checkerApproval->refresh()->approvalDecision?->is(ApprovalDecision::RETURNED))->toBeTrue();
});

it('locks and unlocks draft documents for concurrent edit protection', function (): void {
    $department = Department::factory()->create();
    $maker = User::factory()->create(['department_id' => $department->id]);
    $otherMaker = User::factory()->create(['department_id' => $department->id]);

    grantMakerPermissions($maker);
    grantMakerPermissions($otherMaker);

    $template = createPublishedTemplate($department);

    $document = SopDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $template->publishedVersion->id,
        'department_id' => $department->id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'created_by' => $maker->id,
        'owner_id' => $maker->id,
    ]);

    app(LockDocumentAction::class)->execute($document, $maker);

    expect($document->refresh()->isLockedBy($maker))->toBeTrue()
        ->and($document->canBeEditedBy($otherMaker))->toBeFalse()
        ->and($document->canBeEditedBy($maker))->toBeTrue();

    expect(SopAuditLog::query()->where('document_id', $document->id)->where('action', SopAuditLog::ACTION_LOCKED)->exists())->toBeTrue();
});

it('resolves department-specific workflows before global defaults', function (): void {
    $department = Department::factory()->create(['code' => 'PROD']);
    $maker = User::factory()->create(['department_id' => $department->id]);
    grantMakerPermissions($maker);

    $checkerRole = Role::findOrCreate(SopRole::CHECKER, 'web');
    $approverRole = Role::findOrCreate(SopRole::APPROVER, 'web');

    SopWorkflow::factory()->create([
        'name' => 'Global Workflow',
        'department_id' => null,
        'is_active' => true,
    ])->steps()->create([
        'step_no' => 1,
        'role_id' => $checkerRole->id,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'is_mandatory' => true,
    ]);

    $departmentWorkflow = createApprovalWorkflow($department, $checkerRole, $approverRole);
    $template = createPublishedTemplate($department);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Department Routed SOP',
        ownerId: $maker->id,
        createdBy: $maker->id,
        variables: [],
        documentNumber: 'SOP-PROD-00001',
    ));

    app(SubmitDocumentAction::class)->execute($document, $maker);

    expect($document->refresh()->approvals)->toHaveCount(2)
        ->and($document->approvals->pluck('workflow_step_id')->sort()->values()->all())
        ->toEqual($departmentWorkflow->steps->pluck('id')->sort()->values()->all());
});

it('prevents checkers from approving documents outside their department', function (): void {
    $qaDepartment = Department::factory()->create(['code' => 'QA']);
    $prodDepartment = Department::factory()->create(['code' => 'PROD']);

    $maker = User::factory()->create(['department_id' => $qaDepartment->id]);
    $prodChecker = User::factory()->create(['department_id' => $prodDepartment->id]);

    grantMakerPermissions($maker);

    $checkerRole = Role::findOrCreate(SopRole::CHECKER, 'web');
    $approverRole = Role::findOrCreate(SopRole::APPROVER, 'web');

    grantApproverPermissions($prodChecker);
    $prodChecker->assignRole($checkerRole);

    createApprovalWorkflow($qaDepartment, $checkerRole, $approverRole);
    $template = createPublishedTemplate($qaDepartment);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Cross Department SOP',
        ownerId: $maker->id,
        createdBy: $maker->id,
        variables: [],
        documentNumber: 'SOP-QA-00004',
    ));

    app(SubmitDocumentAction::class)->execute($document, $maker);

    $checkerApproval = $document->approvals()->whereHas('workflowStep', fn ($q) => $q->where('step_no', 1))->first();

    expect($checkerApproval->canBeApprovedBy($prodChecker))->toBeFalse();
});

it('routes approval steps to the department configured on the step', function (): void {
    $qaDepartment = Department::factory()->create(['code' => 'QA']);
    $prodDepartment = Department::factory()->create(['code' => 'PROD']);

    $maker = User::factory()->create(['department_id' => $prodDepartment->id]);
    $prodChecker = User::factory()->create(['department_id' => $prodDepartment->id]);
    $qaApprover = User::factory()->create(['department_id' => $qaDepartment->id]);

    grantMakerPermissions($maker);

    $checkerRole = Role::findOrCreate(SopRole::CHECKER, 'web');
    $approverRole = Role::findOrCreate(SopRole::APPROVER, 'web');

    grantApproverPermissions($prodChecker);
    grantApproverPermissions($qaApprover);
    $prodChecker->assignRole($checkerRole);
    $qaApprover->assignRole($approverRole);

    createApprovalWorkflow($prodDepartment, $checkerRole, $approverRole, $qaDepartment->id);
    $template = createPublishedTemplate($prodDepartment);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Step Department SOP',
        ownerId: $maker->id,
        createdBy: $maker->id,
        variables: [],
        documentNumber: 'SOP-PROD-00002',
    ));

    app(SubmitDocumentAction::class)->execute($document, $maker);

    $checkerApproval = $document->approvals()->whereHas('workflowStep', fn ($q) => $q->where('step_no', 1))->first();
    $approverApproval = $document->approvals()->whereHas('workflowStep', fn ($q) => $q->where('step_no', 2))->first();

    expect($checkerApproval->canBeApprovedBy($prodChecker))->toBeTrue()
        ->and($approverApproval->canBeApprovedBy($prodChecker))->toBeFalse();

    app(ApproveDocumentAction::class)->execute($checkerApproval, $prodChecker, 'Checked by production');

    expect($approverApproval->refresh()->canBeApprovedBy($qaApprover))->toBeTrue()
        ->and($approverApproval->canBeApprovedBy($prodChecker))->toBeFalse();
});
