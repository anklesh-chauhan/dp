<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
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

arch('Shared workflow contracts are interfaces')
    ->expect([
        ApprovalInstancePersistence::class,
        ApprovalWorkflowDefinition::class,
        ApprovalWorkflowDefinitionSelector::class,
        ApprovalWorkflowStepDefinition::class,
    ])
    ->toBeInterfaces();

arch('Shared workflow contracts do not depend on product modules')
    ->expect([
        ApprovalInstancePersistence::class,
        ApprovalWorkflowDefinition::class,
        ApprovalWorkflowDefinitionSelector::class,
        ApprovalWorkflowStepDefinition::class,
    ])
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\DMS',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
        'App\Services\Sop',
    ]);
