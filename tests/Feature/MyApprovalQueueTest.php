<?php

declare(strict_types=1);

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Filament\Pages\MyApprovalQueue;
use App\Filament\Resources\DocumentTemplateApprovalInstances\DocumentTemplateApprovalInstanceResource;
use App\Filament\Resources\SopApprovals\SopApprovalResource;
use App\Filament\Support\MyApprovalQueueService;
use App\Filament\Widgets\PendingApprovalsTable;
use App\Models\ApprovalDecision;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
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
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        'Approve:ControlledDocument',
        'Decide:DocumentTemplateApproval',
        'Decide:QualityApproval',
        'Investigate:Deviation',
        'View:ControlledDocument',
        'View:DocumentTemplate',
        'View:DocumentTemplateApproval',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->department = Department::factory()->create();
    $this->reviewerRole = Role::findOrCreate('central queue reviewer', 'web');
    $this->author = User::factory()->create(['department_id' => $this->department]);
    $this->reviewer = User::factory()->create(['department_id' => $this->department]);
    $this->reviewer->assignRole($this->reviewerRole);
    $this->reviewer->assignRole(Role::findOrCreate('panel_user', 'web'));
    $this->reviewer->givePermissionTo([
        'Approve:ControlledDocument',
        'Decide:DocumentTemplateApproval',
        'Decide:QualityApproval',
        'Investigate:Deviation',
        'View:ControlledDocument',
        'View:DocumentTemplate',
        'View:DocumentTemplateApproval',
    ]);
});

it('combines only actionable document template and QMS approvals for the signed-in user', function (): void {
    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $reportTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
        'created_by' => $this->author,
        'updated_by' => $this->author,
    ]);
    $template = DocumentTemplate::factory()->create([
        'category_id' => DocumentCategory::factory(),
        'department_id' => $this->department,
        'created_by' => $this->author,
        'document_type_id' => $documentType,
        'report_template_id' => $reportTemplate,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
        'created_by' => $this->author,
        'submitted_by' => $this->author,
        'submitted_at' => now(),
        'approval_status' => TemplateApprovalStatus::Submitted,
    ]);
    $workflow = SopWorkflow::factory()->create(['department_id' => $this->department]);
    $step = SopWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
        'step_no' => 1,
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $this->department,
        'created_by' => $this->author,
        'owner_id' => $this->author,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
    ]);
    SopApproval::factory()->create([
        'document_id' => $document,
        'workflow_step_id' => $step,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);
    DocumentTemplateApprovalInstance::factory()->create([
        'document_template_version_id' => $templateVersion,
        'workflow_id' => $workflow,
        'workflow_step_id' => $step,
    ]);

    $qualityWorkflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
    ]);
    $qualityStep = QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $qualityWorkflow,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
    ]);
    $deviation = Deviation::factory()->create([
        'department_id' => $this->department,
        'reported_by' => $this->author,
        'status' => 'open',
    ]);
    QualityApprovalInstance::factory()->create([
        'subject_type' => Deviation::class,
        'subject_id' => $deviation,
        'workflow_id' => $qualityWorkflow,
        'workflow_step_id' => $qualityStep,
    ]);

    $this->actingAs($this->reviewer);
    $items = app(MyApprovalQueueService::class)->forUser($this->reviewer);

    expect($items)->toHaveCount(3)
        ->and($items->pluck('work_type')->sort()->values()->all())
        ->toBe(['Controlled Document', 'Deviation', 'Document Template'])
        ->and($items->every(fn (array $item): bool => filled($item['review_url'])))->toBeTrue()
        ->and($items->firstWhere('work_type', 'Controlled Document')['print_preview_url'])->not->toBeNull()
        ->and($items->firstWhere('work_type', 'Document Template')['print_preview_url'])->not->toBeNull()
        ->and($items->firstWhere('work_type', 'Deviation')['print_preview_url'])->toBeNull()
        ->and(MyApprovalQueue::canAccess())->toBeTrue();

    Livewire::test(MyApprovalQueue::class)
        ->assertOk()
        ->assertSee('Controlled Document')
        ->assertSee('Document Template')
        ->assertSee('Deviation')
        ->assertActionVisible(TestAction::make('printPreview')->table($items->firstWhere('work_type', 'Controlled Document')['id']));

    Livewire::test(PendingApprovalsTable::class)
        ->assertSee('Controlled Document')
        ->assertSee('Document Template')
        ->assertSee('Deviation');
});

it('does not expose unavailable work or duplicate queue navigation', function (): void {
    $unauthorizedUser = User::factory()->create();
    $this->actingAs($unauthorizedUser);

    expect(app(MyApprovalQueueService::class)->forUser($unauthorizedUser))->toBeEmpty()
        ->and(MyApprovalQueue::canAccess())->toBeFalse()
        ->and(SopApprovalResource::shouldRegisterNavigation())->toBeFalse()
        ->and(DocumentTemplateApprovalInstanceResource::shouldRegisterNavigation())->toBeFalse();
});
