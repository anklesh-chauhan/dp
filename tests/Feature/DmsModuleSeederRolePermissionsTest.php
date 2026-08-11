<?php

declare(strict_types=1);

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Filament\Support\MyApprovalQueueService;
use App\Models\ApprovalStepType;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    config()->set('modules.enabled', ['dms']);
    $this->seed(DatabaseSeeder::class);
});

it('seeds Decide:DocumentTemplateApproval on the sop checker role', function (): void {
    $checkerRole = Role::findByName('sop checker', 'web');
    $checker = User::query()->where('email', 'Checker@example.com')->first();

    expect($checkerRole->hasPermissionTo('Decide:DocumentTemplateApproval'))->toBeTrue()
        ->and($checkerRole->hasPermissionTo('View:DocumentTemplateApproval'))->toBeTrue()
        ->and($checker)->not->toBeNull()
        ->and($checker->can('Decide:DocumentTemplateApproval'))->toBeTrue();
});

it('includes pending template approvals in the checker approval queue after seeding', function (): void {
    $checker = User::query()->where('email', 'Checker@example.com')->firstOrFail();
    $author = User::query()->where('email', 'Maker@example.com')->firstOrFail();
    $department = Department::query()->firstOrFail();
    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $category = DocumentCategory::factory()->create();
    $template = DocumentTemplate::factory()->create([
        'category_id' => $category,
        'department_id' => $department,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
        'created_by' => $author,
    ]);
    $version = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
        'created_by' => $author,
        'submitted_by' => $author,
        'submitted_at' => now(),
        'approval_status' => TemplateApprovalStatus::Submitted,
    ]);
    $workflow = SopWorkflow::factory()->create([
        'department_id' => $department,
        'is_active' => true,
    ]);
    $step = SopWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'step_no' => 1,
        'role_id' => Role::findByName('sop checker', 'web')->id,
        'department_id' => null,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'is_mandatory' => true,
    ]);
    DocumentTemplateApprovalInstance::factory()->create([
        'document_template_version_id' => $version,
        'workflow_id' => $workflow,
        'workflow_step_id' => $step,
    ]);

    $items = app(MyApprovalQueueService::class)->forUser($checker);

    expect($items->firstWhere('work_type', 'Document Template'))->not->toBeNull()
        ->and($items->firstWhere('work_type', 'Document Template')['required_role'])->toBe('sop checker');
});
