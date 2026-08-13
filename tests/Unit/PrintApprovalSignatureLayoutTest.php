<?php

declare(strict_types=1);

use App\Domain\Reporting\Support\PrintApprovalSignatureLayout;
use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\SopApproval;
use App\Models\SopWorkflowStep;
use App\Models\User;
use App\Support\Formatting\DateFormatSettings;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
});

it('groups the document owner as prepared by and workflow steps into reviewed and approved blocks', function (): void {
    $qa = Department::factory()->create(['name' => 'Quality Assurance']);
    $production = Department::factory()->create(['name' => 'Production']);
    $ownerDesignation = Designation::factory()->create(['name' => 'Document Controller', 'code' => 'DOC_CTRL']);
    $reviewerDesignation = Designation::factory()->create(['name' => 'Production Supervisor', 'code' => 'PROD_SUP']);
    $approverDesignation = Designation::factory()->create(['name' => 'QA Manager', 'code' => 'QA_MGR']);

    $owner = User::factory()->create([
        'name' => 'Neha Shah',
        'department_id' => $qa->id,
        'designation_id' => $ownerDesignation->id,
    ]);
    $reviewer = User::factory()->create([
        'name' => 'Ravi Patel',
        'department_id' => $production->id,
        'designation_id' => $reviewerDesignation->id,
    ]);
    $approver = User::factory()->create([
        'name' => 'Asha Verma',
        'department_id' => $qa->id,
        'designation_id' => $approverDesignation->id,
    ]);

    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->firstOrFail()->id,
        'department_id' => $qa->id,
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->id,
    ]);

    $document = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'department_id' => $qa->id,
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
    ]);

    $reviewStep = SopWorkflowStep::factory()->create([
        'step_no' => 1,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'department_id' => $production->id,
    ]);
    $approveStep = SopWorkflowStep::factory()->create([
        'step_no' => 2,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::APPROVER),
        'department_id' => $qa->id,
    ]);

    SopApproval::factory()->create([
        'document_id' => $document->id,
        'workflow_step_id' => $reviewStep->id,
        'approved_by' => $reviewer->id,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
        'approved_at' => now(),
    ]);
    SopApproval::factory()->create([
        'document_id' => $document->id,
        'workflow_step_id' => $approveStep->id,
        'approved_by' => $approver->id,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
        'approved_at' => now(),
    ]);

    $document->load([
        'approvals.approver.designation',
        'approvals.approvalDecision',
        'approvals.workflowStep.department',
        'approvals.workflowStep.approvalStepType',
        'creator.department',
        'creator.designation',
        'department',
        'owner.department',
        'owner.designation',
    ]);

    $groups = app(PrintApprovalSignatureLayout::class)->groups($document);
    $signedAt = app(DateFormatSettings::class)->formatDateTime(now());

    expect($groups)->toHaveCount(3)
        ->and(array_column($groups, 'heading'))->toBe([
            PrintApprovalSignatureLayout::PREPARED_BY,
            PrintApprovalSignatureLayout::REVIEWED_BY,
            PrintApprovalSignatureLayout::APPROVED_BY,
        ])
        ->and($groups[0]['entries'][0])->toMatchArray([
            'department' => 'Quality Assurance',
            'name' => 'Neha Shah',
            'designation' => 'Document Controller',
            'signature' => '-',
            'signature_lines' => ['-'],
        ])
        ->and($groups[1]['entries'][0])->toMatchArray([
            'department' => 'Production',
            'name' => 'Ravi Patel',
            'designation' => 'Production Supervisor',
            'signature_lines' => [
                'Electronically signed',
                "Approved · {$signedAt}",
            ],
        ])
        ->and($groups[2]['entries'][0])->toMatchArray([
            'department' => 'Quality Assurance',
            'name' => 'Asha Verma',
            'designation' => 'QA Manager',
            'signature_lines' => [
                'Electronically signed',
                "Approved · {$signedAt}",
            ],
        ]);
});

it('does not claim an electronic signature without a signature hash', function (): void {
    $qa = Department::factory()->create(['name' => 'Quality Assurance']);
    $owner = User::factory()->create([
        'name' => 'Neha Shah',
        'department_id' => $qa->id,
    ]);
    $reviewer = User::factory()->create([
        'name' => 'Ravi Patel',
        'department_id' => $qa->id,
    ]);

    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->firstOrFail()->id,
        'department_id' => $qa->id,
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->id,
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'department_id' => $qa->id,
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
    ]);
    $step = SopWorkflowStep::factory()->create([
        'step_no' => 1,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'department_id' => $qa->id,
    ]);

    SopApproval::factory()->create([
        'document_id' => $document->id,
        'workflow_step_id' => $step->id,
        'approved_by' => $reviewer->id,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
        'approved_at' => now(),
        'signature_hash' => null,
    ]);

    $document->load([
        'approvals.approver.designation',
        'approvals.approvalDecision',
        'approvals.workflowStep.department',
        'approvals.workflowStep.approvalStepType',
        'creator.department',
        'creator.designation',
        'department',
        'owner.department',
        'owner.designation',
    ]);

    $groups = app(PrintApprovalSignatureLayout::class)->groups($document);

    expect($groups[1]['entries'][0]['signature'])->toBe('-')
        ->and($groups[1]['entries'][0]['signature_lines'])->toBe(['-']);
});
