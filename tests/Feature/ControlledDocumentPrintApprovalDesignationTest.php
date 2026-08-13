<?php

declare(strict_types=1);

use App\Domain\DMS\Services\ControlledDocumentPrintPreviewService;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\ReportTemplate;
use App\Models\SopApproval;
use App\Models\SopWorkflowStep;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
});

/**
 * @param  list<array{approver: User, step_type: string, department_id?: int|null}>  $approvals
 */
function renderApprovalSignatures(User $owner, array $approvals): string
{
    actingAs($owner);

    $fields = collect(app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument))
        ->map(function (array $field): array {
            $field['enabled'] = $field['key'] === 'approvals';

            return $field;
        })
        ->all();

    $reportTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => $fields,
        'created_by' => $owner->id,
        'updated_by' => $owner->id,
    ]);

    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->firstOrFail()->id,
        'report_template_id' => $reportTemplate->id,
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->id,
    ]);

    $document = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'created_by' => $owner->id,
        'owner_id' => $owner->id,
        'department_id' => $owner->department_id,
    ]);

    foreach ($approvals as $index => $approval) {
        $step = SopWorkflowStep::factory()->create([
            'step_no' => $index + 1,
            'approval_step_type_id' => ApprovalStepType::idFor($approval['step_type']),
            'department_id' => $approval['department_id'] ?? $approval['approver']->department_id,
        ]);

        SopApproval::factory()->create([
            'document_id' => $document->id,
            'workflow_step_id' => $step->id,
            'approved_by' => $approval['approver']->id,
            'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
            'approved_at' => now(),
        ]);
    }

    return view(
        'controlled-documents.print',
        app(ControlledDocumentPrintPreviewService::class)->viewData($document),
    )->render();
}

it('prints approval signatures in vertical prepared reviewed and approved blocks', function (): void {
    $qa = Department::factory()->create(['name' => 'Quality Assurance']);
    $production = Department::factory()->create(['name' => 'Production']);
    $owner = User::factory()->create([
        'name' => 'Neha Shah',
        'department_id' => $qa->id,
        'designation_id' => Designation::factory()->create([
            'name' => 'Document Controller',
            'code' => 'DOC_CTRL',
        ])->id,
    ]);
    $reviewer = User::factory()->create([
        'name' => 'Ravi Patel',
        'department_id' => $production->id,
        'designation_id' => Designation::factory()->create([
            'name' => 'Production Supervisor',
            'code' => 'PROD_SUP',
        ])->id,
    ]);
    $approver = User::factory()->create([
        'name' => 'Asha Verma',
        'department_id' => $qa->id,
        'designation_id' => Designation::factory()->create([
            'name' => 'QA Manager',
            'code' => 'QA_MGR',
        ])->id,
    ]);

    $html = renderApprovalSignatures($owner, [
        ['approver' => $reviewer, 'step_type' => ApprovalStepType::CHECKER, 'department_id' => $production->id],
        ['approver' => $approver, 'step_type' => ApprovalStepType::APPROVER, 'department_id' => $qa->id],
    ]);

    expect($html)
        ->toContain('class="approval-signatures"')
        ->toContain('Prepared By')
        ->toContain('Reviewed By')
        ->toContain('Approved By')
        ->toContain('Sign &amp; Date')
        ->toContain('Electronically signed')
        ->toContain('Approved ·')
        ->toContain('>Name</th>')
        ->toContain('>Designation</th>')
        ->toContain('rowspan="3"')
        ->toContain('Neha Shah')
        ->toContain('Document Controller')
        ->toContain('Ravi Patel')
        ->toContain('Production Supervisor')
        ->toContain('Asha Verma')
        ->toContain('QA Manager')
        ->toContain('Quality Assurance')
        ->toContain('Production')
        ->toContain('Electronic signatures shown above include the signer identity');
});

it('prints a dash when the approver has no designation', function (): void {
    $department = Department::factory()->create(['name' => 'Quality Assurance']);
    $owner = User::factory()->create([
        'name' => 'Owner User',
        'department_id' => $department->id,
        'designation_id' => null,
    ]);
    $approver = User::factory()->create([
        'name' => 'Ravi Patel',
        'department_id' => $department->id,
        'designation_id' => null,
    ]);

    $html = renderApprovalSignatures($owner, [
        ['approver' => $approver, 'step_type' => ApprovalStepType::APPROVAL, 'department_id' => $department->id],
    ]);

    expect($html)
        ->toContain('Ravi Patel')
        ->toContain('>Designation</th>')
        ->toMatch('/Designation<\/th>\s*<td class="signature-value">-<\/td>/');
});
