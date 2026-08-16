<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use App\Domain\Shared\Contracts\WorkflowDecisionNotifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Domain\Shared\Services\ApprovalWorkflowEngineService;
use App\Models\ApprovalDecision;
use App\Models\ControlledDocument;
use App\Models\SopApproval;
use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;

it('adapts the existing SOP workflow model to the Shared workflow definition contract', function (): void {
    $workflow = new SopWorkflow;
    $workflow->setAttribute('id', 42);

    expect($workflow)
        ->toBeInstanceOf(ApprovalWorkflowDefinition::class)
        ->and($workflow->approvalWorkflowDefinitionKey())->toBe(42);
});

it('adapts the existing SOP workflow step model to the Shared step definition contract', function (): void {
    $step = new SopWorkflowStep;
    $step->setAttribute('id', 84);

    expect($step)
        ->toBeInstanceOf(ApprovalWorkflowStepDefinition::class)
        ->and($step->approvalWorkflowStepDefinitionKey())->toBe(84);
});

it('adapts the existing SOP approval model to the Shared approval instance contract', function (): void {
    $subject = new ControlledDocument([
        'document_number' => 'SOP-QA-00042',
        'title' => 'Shared approval instance',
    ]);
    $step = new SopWorkflowStep;
    $step->setAttribute('id', 84);
    $decision = new ApprovalDecision([
        'code' => ApprovalDecision::APPROVED,
        'name' => 'Approved',
    ]);

    $approval = new SopApproval([
        'approved_by' => 21,
        'comments' => 'Reviewed and approved.',
        'signature_hash' => 'signature-hash',
    ]);
    $approval->setAttribute('id', 126);
    $approval->setRelation('document', $subject);
    $approval->setRelation('workflowStep', $step);
    $approval->setRelation('approvalDecision', $decision);

    expect($approval)
        ->toBeInstanceOf(ApprovalInstance::class)
        ->and($approval->approvalInstanceKey())->toBe(126)
        ->and($approval->approvalInstanceSubject())->toBe($subject)
        ->and($approval->approvalInstanceWorkflowStepDefinition())->toBe($step)
        ->and($approval->approvalInstanceDecisionCode())->toBe(ApprovalDecision::APPROVED)
        ->and($approval->approvalInstanceApproverId())->toBe(21)
        ->and($approval->approvalInstanceComments())->toBe('Reviewed and approved.')
        ->and($approval->approvalInstanceDecidedAt())->toBeNull()
        ->and($approval->approvalInstanceSignatureHash())->toBe('signature-hash');
});

it('keeps Shared approval decision codes compatible with the existing DMS lookup model', function (): void {
    expect(ApprovalDecisionCode::PENDING->value)->toBe(ApprovalDecision::PENDING)
        ->and(ApprovalDecisionCode::APPROVED->value)->toBe(ApprovalDecision::APPROVED)
        ->and(ApprovalDecisionCode::REJECTED->value)->toBe(ApprovalDecision::REJECTED)
        ->and(ApprovalDecisionCode::RETURNED->value)->toBe(ApprovalDecision::RETURNED);
});

arch('Shared workflow contracts are interfaces')
    ->expect([
        ApprovalDecisionAuthorization::class,
        ApprovalDecisionOutcome::class,
        ApprovalDecisionPersistence::class,
        ApprovalInstance::class,
        ApprovalInstancePersistence::class,
        ApprovalSubmissionAuthorization::class,
        ApprovalSubmissionLifecycle::class,
        ApprovalWorkflowDefinition::class,
        ApprovalWorkflowDefinitionSelector::class,
        ApprovalWorkflowStepDefinition::class,
        WorkflowDecisionNotifier::class,
    ])
    ->toBeInterfaces();

arch('Shared workflow contracts do not depend on product modules')
    ->expect([
        ApprovalDecisionAuthorization::class,
        ApprovalDecisionOutcome::class,
        ApprovalDecisionPersistence::class,
        ApprovalInstance::class,
        ApprovalInstancePersistence::class,
        ApprovalSubmissionAuthorization::class,
        ApprovalSubmissionLifecycle::class,
        ApprovalWorkflowDefinition::class,
        ApprovalWorkflowDefinitionSelector::class,
        ApprovalWorkflowStepDefinition::class,
        WorkflowDecisionNotifier::class,
    ])
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\DMS',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
        'App\Services\Sop',
    ]);

arch('Shared approval decision vocabulary does not depend on product modules')
    ->expect(ApprovalDecisionCode::class)
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\DMS',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
        'App\Services\Sop',
    ]);

arch('canonical Shared workflow engine does not depend on product modules')
    ->expect(ApprovalWorkflowEngineService::class)
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\DMS',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
        'App\Services\Sop',
    ]);
