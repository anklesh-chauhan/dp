<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\PublishTemplateAction;
use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\DMS\Services\TemplateApprovalDecisionService;
use App\Domain\DMS\Services\TemplateApprovalService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Exceptions\WorkflowException;
use App\Models\ApprovalStepType;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateApprovalEvent;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\DocumentTemplateVersion;
use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms']);

    foreach ([TemplateStatus::DRAFT => 'Draft', TemplateStatus::PUBLISHED => 'Published'] as $code => $name) {
        TemplateStatus::query()->create(compact('code', 'name'));
    }

    foreach ([
        ApprovalStepType::CHECKER => 'Checker',
        ApprovalStepType::APPROVER => 'Approver',
        ApprovalStepType::APPROVAL => 'Approval',
    ] as $code => $name) {
        ApprovalStepType::query()->create(compact('code', 'name'));
    }

    foreach (['Submit:DocumentTemplate', 'Decide:DocumentTemplateApproval', 'Publish:DocumentTemplate'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->reviewerRole = Role::findOrCreate('qa reviewer', 'web');
    $this->approverRole = Role::findOrCreate('sop approver', 'web');
    $this->author = User::factory()->create()->givePermissionTo('Submit:DocumentTemplate');
    $this->reviewer = User::factory()->create();
    $this->reviewer->assignRole($this->reviewerRole);
    $this->reviewer->givePermissionTo('Decide:DocumentTemplateApproval');
    $this->approver = User::factory()->create();
    $this->approver->assignRole($this->approverRole);
    $this->approver->givePermissionTo('Decide:DocumentTemplateApproval');
    $this->publisher = User::factory()->create()->givePermissionTo('Publish:DocumentTemplate');

    $this->template = DocumentTemplate::factory()->create(['created_by' => $this->author->id]);
    $this->version = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $this->template->id,
        'created_by' => $this->author->id,
    ]);
    $this->workflow = SopWorkflow::factory()->create([
        'department_id' => $this->template->department_id,
        'is_active' => true,
    ]);
    SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow->id,
        'step_no' => 1,
        'role_id' => $this->reviewerRole->id,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'is_mandatory' => true,
    ]);
    SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow->id,
        'step_no' => 2,
        'role_id' => $this->approverRole->id,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::APPROVER),
        'is_mandatory' => true,
    ]);
});

it('selects the department SOP workflow and creates ordered template approval instances', function (): void {
    $submitted = app(TemplateApprovalService::class)->submit(
        $this->template,
        $this->author,
        'Ready for configured workflow review.',
    );

    $instances = $submitted->approvalInstances()->with('workflowStep')->orderBy('id')->get();

    expect($submitted->approval_status)->toBe(TemplateApprovalStatus::Submitted)
        ->and($instances)->toHaveCount(2)
        ->and($instances->pluck('workflow_id')->unique()->all())->toBe([$this->workflow->id])
        ->and($instances->pluck('workflowStep.step_no')->all())->toBe([1, 2])
        ->and($instances->pluck('decision_code')->unique()->all())->toBe(['pending']);
});

it('shows under review instead of draft while mandatory approval steps remain pending', function (): void {
    $version = app(TemplateApprovalService::class)->submit(
        $this->template,
        $this->author,
        'Ready for configured workflow review.',
    );
    [$review] = $version->approvalInstances()->orderBy('id')->get()->all();

    expect($this->template->refresh()->displayStatusLabel())->toBe('Under Review')
        ->and($this->template->displayStatusColor())->toBe('warning')
        ->and($version->approval_status->label())->toBe('Under Review');

    app(TemplateApprovalDecisionService::class)->decide(
        $review,
        $this->reviewer,
        ApprovalDecisionCode::APPROVED,
        'First step signed; second step still pending.',
    );

    expect($version->refresh()->approval_status)->toBe(TemplateApprovalStatus::Submitted)
        ->and($this->template->refresh()->displayStatusLabel())->toBe('Under Review')
        ->and($this->template->templateStatus?->code)->toBe(TemplateStatus::DRAFT);
});

it('falls back to the active global SOP workflow', function (): void {
    $this->workflow->update(['is_active' => false]);
    $global = SopWorkflow::factory()->create(['department_id' => null, 'is_active' => true]);
    SopWorkflowStep::factory()->create([
        'workflow_id' => $global->id,
        'step_no' => 1,
        'role_id' => $this->reviewerRole->id,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
    ]);

    $submitted = app(TemplateApprovalService::class)->submit(
        $this->template,
        $this->author,
        'Use global workflow.',
    );

    expect($submitted->approvalInstances()->sole()->workflow_id)->toBe($global->id);
});

it('requires ordered separate users and signs every workflow decision', function (): void {
    $version = app(TemplateApprovalService::class)->submit(
        $this->template,
        $this->author,
        'Submit for independent review.',
    );
    [$review, $approval] = $version->approvalInstances()->orderBy('id')->get()->all();
    $decisions = app(TemplateApprovalDecisionService::class);

    expect(fn () => $decisions->decide(
        $approval,
        $this->approver,
        ApprovalDecisionCode::APPROVED,
        'Attempt out of order.',
    ))->toThrow(WorkflowException::class, 'previous mandatory');

    $review = $decisions->decide(
        $review,
        $this->reviewer,
        ApprovalDecisionCode::APPROVED,
        'Technical and GMP review completed.',
        '127.0.0.1',
        'Pest',
    );

    $this->reviewer->assignRole($this->approverRole);

    expect(fn () => $decisions->decide(
        $approval,
        $this->reviewer,
        ApprovalDecisionCode::APPROVED,
        'Same user attempts second step.',
    ))->toThrow(WorkflowException::class, 'different user');

    $approval = $decisions->decide(
        $approval,
        $this->approver,
        ApprovalDecisionCode::APPROVED,
        'Final QA approval.',
        '127.0.0.1',
        'Pest',
    );

    expect(app(ElectronicSignatureVerifier::class)->isValid($review))->toBeTrue()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approval))->toBeTrue()
        ->and($version->refresh()->approval_status)->toBe(TemplateApprovalStatus::Approved);
});

it('prevents authors from deciding and returns rejected submissions for correction', function (): void {
    $version = app(TemplateApprovalService::class)->submit(
        $this->template,
        $this->author,
        'Submit for review.',
    );
    $instance = $version->approvalInstances()->orderBy('id')->firstOrFail();
    $this->author->assignRole($this->reviewerRole);
    $this->author->givePermissionTo('Decide:DocumentTemplateApproval');

    expect(fn () => app(TemplateApprovalDecisionService::class)->decide(
        $instance,
        $this->author,
        ApprovalDecisionCode::APPROVED,
        'Self decision.',
    ))->toThrow(WorkflowException::class, 'author or submitter');

    $rejected = app(TemplateApprovalDecisionService::class)->decide(
        $instance,
        $this->reviewer,
        ApprovalDecisionCode::REJECTED,
        'Responsibilities require correction.',
        '127.0.0.1',
        'Pest',
    );

    expect(app(ElectronicSignatureVerifier::class)->isValid($rejected))->toBeTrue()
        ->and($version->refresh()->approval_status)->toBe(TemplateApprovalStatus::Rejected)
        ->and($this->template->refresh()->canBeEditedBy($this->author))->toBeTrue();
});

it('publishes only after every mandatory workflow approval has a valid signature', function (): void {
    $this->template->update(['current_version' => 1]);
    $version = app(TemplateApprovalService::class)->submit(
        $this->template,
        $this->author,
        'Submit generated version one.',
    );
    [$review, $approval] = $version->approvalInstances()->orderBy('id')->get()->all();
    $decisions = app(TemplateApprovalDecisionService::class);
    $decisions->decide($review, $this->reviewer, ApprovalDecisionCode::APPROVED, 'Reviewed.');

    expect(fn () => app(PublishTemplateAction::class)->execute(
        $this->template,
        $this->publisher->id,
        'Premature publication.',
    ))->toThrow(ValidationException::class);

    $decisions->decide($approval, $this->approver, ApprovalDecisionCode::APPROVED, 'Approved.');

    $published = app(PublishTemplateAction::class)->execute(
        $this->template,
        $this->publisher->id,
        'First controlled release.',
    );

    expect($published->version)->toBe(1)
        ->and($this->template->refresh()->current_version)->toBe(1)
        ->and($this->template->isEditable())->toBeFalse()
        ->and($this->template->canBeEditedBy($this->author))->toBeFalse()
        ->and($published->isContentEditable())->toBeFalse();
});

it('preserves submission audit events and workflow approval instances', function (): void {
    app(TemplateApprovalService::class)->submit($this->template, $this->author, 'Submit.');

    expect(DocumentTemplateApprovalEvent::query()->count())->toBe(1)
        ->and(DocumentTemplateApprovalInstance::query()->count())->toBe(2);
});
