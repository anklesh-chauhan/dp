<?php

declare(strict_types=1);

use App\Actions\Sop\ReturnDocumentAction;
use App\Actions\Sop\SubmitDocumentAction;
use App\Domain\DMS\Actions\AddSectionReviewCommentAction;
use App\Domain\DMS\Actions\ResolveSectionReviewCommentAction;
use App\Domain\DMS\Services\TemplateApprovalService;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Domain\QMS\Services\DeviationApprovalDecisionService;
use App\Domain\QMS\Services\DeviationApprovalSubmissionService;
use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\SopApproval;
use App\Models\SopRole;
use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        'Approve:ControlledDocument',
        'Approve:SopApproval',
        'Submit:ControlledDocument',
        'View:ControlledDocument',
        'Update:ControlledDocument',
        'Submit:DocumentTemplate',
        'Decide:DocumentTemplateApproval',
        'Submit:Deviation',
        'Investigate:Deviation',
        'Decide:QualityApproval',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->department = Department::factory()->create();
    $this->reviewerRole = Role::findOrCreate('workflow notification reviewer', 'web');
    $this->author = User::factory()->create(['department_id' => $this->department]);
    $this->reviewer = User::factory()->create(['department_id' => $this->department]);
    $this->reviewer->assignRole([$this->reviewerRole, Role::findOrCreate('panel_user', 'web')]);
    $this->reviewer->givePermissionTo([
        'Approve:ControlledDocument',
        'Approve:SopApproval',
        'View:ControlledDocument',
        'Decide:DocumentTemplateApproval',
        'Investigate:Deviation',
        'Decide:QualityApproval',
    ]);
    $this->author->givePermissionTo([
        'Submit:ControlledDocument',
        'Submit:DocumentTemplate',
        'Submit:Deviation',
        'Update:ControlledDocument',
        'View:ControlledDocument',
    ]);

    Gate::before(static fn (): bool => true);
});

function notificationTitles(User $user): array
{
    return $user->notifications()
        ->latest()
        ->get()
        ->map(fn ($notification): ?string => $notification->data['title'] ?? null)
        ->filter()
        ->values()
        ->all();
}

function workflowDocument(User $author, Department $department, Role $reviewerRole): array
{
    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $category = DocumentCategory::factory()->create();
    $template = DocumentTemplate::factory()->create([
        'category_id' => $category,
        'department_id' => $department,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'created_by' => $author,
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template,
        'created_by' => $author,
    ]);
    $workflow = SopWorkflow::factory()->create([
        'department_id' => $department,
        'is_active' => true,
    ]);
    $step = SopWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'step_no' => 1,
        'role_id' => $reviewerRole,
        'department_id' => $department,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'is_mandatory' => true,
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'created_by' => $author,
        'owner_id' => $author,
    ]);

    return compact('document', 'workflow', 'step', 'template', 'templateVersion');
}

it('notifies the current reviewer when a document is submitted and never notifies the maker', function (): void {
    ['document' => $document] = workflowDocument($this->author, $this->department, $this->reviewerRole);

    app(SubmitDocumentAction::class)->execute($document, $this->author);

    expect(notificationTitles($this->reviewer))->toContain('Document submitted for your review')
        ->and(notificationTitles($this->author))->toBe([]);
});

it('notifies the maker when a reviewer comments on a section', function (): void {
    ['document' => $document, 'step' => $step] = workflowDocument($this->author, $this->department, $this->reviewerRole);
    $document->update(['document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW)]);
    SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $step,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'title' => 'Purpose',
        'section_order' => 1,
    ]);

    app(AddSectionReviewCommentAction::class)->execute(
        $section,
        $this->reviewer,
        'Replace purified water with WFI.',
    );

    expect(notificationTitles($this->author))->toContain('Reviewer commented on Purpose')
        ->and(notificationTitles($this->reviewer))->toBe([]);
});

it('notifies the maker when a document is returned and the reviewer when a comment is addressed', function (): void {
    ['document' => $document, 'step' => $step] = workflowDocument($this->author, $this->department, $this->reviewerRole);
    $document->update(['document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW)]);
    $approval = SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $step,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'title' => 'Scope',
        'section_order' => 1,
    ]);
    $comment = app(AddSectionReviewCommentAction::class)->execute(
        $section,
        $this->reviewer,
        'Name the equipment ID.',
    );

    app(ReturnDocumentAction::class)->execute($approval, $this->reviewer, 'Return the flagged sections.');

    $document->refresh();
    app(ResolveSectionReviewCommentAction::class)->execute($comment->fresh(), $this->author);

    expect(notificationTitles($this->author))->toContain('Document returned for correction')
        ->and(notificationTitles($this->reviewer))->toContain('Maker addressed your comment on Scope');
});

it('notifies template reviewers when a template is submitted', function (): void {
    $template = DocumentTemplate::factory()->create([
        'department_id' => $this->department,
        'document_type_id' => DocumentType::query()->where('code', DocumentType::SOP)->valueOrFail('id'),
        'created_by' => $this->author,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
        'created_by' => $this->author,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $workflow = SopWorkflow::factory()->create([
        'department_id' => $this->department,
        'is_active' => true,
    ]);
    SopWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'step_no' => 1,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'is_mandatory' => true,
    ]);

    app(TemplateApprovalService::class)->submit($template, $this->author, 'Please review the draft template.');

    expect(notificationTitles($this->reviewer))->toContain('Template submitted for your review')
        ->and(notificationTitles($this->author))->toBe([]);
});

it('notifies the submitting maker when a document they did not create is returned', function (): void {
    $this->author->assignRole(Role::findOrCreate(SopRole::MAKER, 'web'));

    $originator = User::factory()->create(['department_id' => $this->department]);
    ['document' => $document] = workflowDocument($originator, $this->department, $this->reviewerRole);

    $document = app(SubmitDocumentAction::class)->execute($document, $this->author);
    $approval = $document->approvals->first();

    expect($approval)->toBeInstanceOf(SopApproval::class);

    app(ReturnDocumentAction::class)->execute($approval, $this->reviewer, 'Return the flagged sections.');

    expect(notificationTitles($this->author))->toContain('Document returned for correction')
        ->and(notificationTitles($originator))->toContain('Document returned for correction');
});

it('notifies quality reviewers when a deviation is submitted', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
        'is_active' => true,
    ]);
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'step_no' => 1,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
        'is_mandatory' => true,
    ]);
    $deviation = Deviation::factory()->create([
        'department_id' => $this->department,
        'reported_by' => $this->author,
        'owner_id' => $this->author,
    ]);

    app(DeviationApprovalSubmissionService::class)->submit(
        $deviation,
        $this->author,
        'Submit for independent quality review.',
    );

    expect(notificationTitles($this->reviewer))->toContain('Deviation submitted for your review')
        ->and(notificationTitles($this->author))->toBe([]);
});

it('notifies the deviation reporter when quality review is returned', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
        'is_active' => true,
    ]);
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'step_no' => 1,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
        'is_mandatory' => true,
    ]);
    $deviation = Deviation::factory()->create([
        'department_id' => $this->department,
        'reported_by' => $this->author,
        'owner_id' => $this->author,
    ]);

    $submitted = app(DeviationApprovalSubmissionService::class)->submit(
        $deviation,
        $this->author,
        'Submit for independent quality review.',
    );
    $instance = $submitted->approvalInstances()->sole();

    app(DeviationApprovalDecisionService::class)->return(
        $instance,
        $this->reviewer,
        'Clarify the event chronology before resubmission.',
    );

    expect(notificationTitles($this->author))->toContain('Deviation returned for correction')
        ->and(notificationTitles($this->reviewer))->toContain('Deviation submitted for your review')
        ->and(notificationTitles($this->reviewer))->not->toContain('Deviation returned for correction');
});

it('notifies the next quality reviewer after the first deviation step is approved', function (): void {
    $secondReviewer = User::factory()->create(['department_id' => $this->department]);
    $secondRole = Role::findOrCreate('workflow notification second reviewer', 'web');
    $secondReviewer->assignRole([$secondRole, Role::findOrCreate('panel_user', 'web')]);
    $secondReviewer->givePermissionTo([
        'Investigate:Deviation',
        'Decide:QualityApproval',
    ]);

    $workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
        'is_active' => true,
    ]);
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'step_no' => 1,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
        'is_mandatory' => true,
    ]);
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'step_no' => 2,
        'role_id' => $secondRole,
        'department_id' => $this->department,
        'is_mandatory' => true,
    ]);
    $deviation = Deviation::factory()->create([
        'department_id' => $this->department,
        'reported_by' => $this->author,
        'owner_id' => $this->author,
    ]);

    $submitted = app(DeviationApprovalSubmissionService::class)->submit(
        $deviation,
        $this->author,
        'Submit for two-level quality review.',
    );
    $first = $submitted->approvalInstances()
        ->orderBy('id')
        ->firstOrFail();

    app(DeviationApprovalDecisionService::class)->approve(
        $first,
        $this->reviewer,
        'Initial quality review approved.',
    );

    expect(notificationTitles($secondReviewer))->toContain('Deviation is waiting for your approval')
        ->and(notificationTitles($this->author))->toBe([])
        ->and(notificationTitles($this->reviewer))->toContain('Deviation submitted for your review');
});

it('can count unread filament database notifications with a json path query', function (): void {
    ['document' => $document] = workflowDocument($this->author, $this->department, $this->reviewerRole);

    app(SubmitDocumentAction::class)->execute($document, $this->author);

    expect(
        $this->reviewer->unreadNotifications()->where('data->format', 'filament')->count()
    )->toBe(1);
});
